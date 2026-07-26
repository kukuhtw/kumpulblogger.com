<?php
session_start();
// admin/publisher_click_forensics.php
// Read-only investigation tool: inspect a single publisher's click history for
// fraud signals (IP/UA reuse, bot user agents, referrer/domain mismatches,
// rapid-fire bursts) — built to investigate cases like "klik diakui tapi
// skrip iklan tidak ada di halaman itu".

if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}

include("../db.php");
include("../function.php");

$loginemail_admin = $_SESSION['loginemail_admin'];
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit("Database connection failed.");
}

$pub_id = isset($_GET['pub_id']) ? (int) $_GET['pub_id'] : 43;
$click_limit = 500;
$burst_window_seconds = isset($_GET['burst_window']) ? max(1, (int) $_GET['burst_window']) : 30;

// Publisher info
$pub_stmt = $mysqli->prepare(
    "SELECT ps.id, ps.site_name, ps.site_domain, ps.providers_name, ps.regdate,
            ps.current_site_revenue, ps.current_site_revenue_from_partner,
            mu.id AS owner_id, mu.loginemail AS owner_email
     FROM publishers_site ps
     LEFT JOIN msusers mu ON ps.publishers_local_id = mu.id
     WHERE ps.id = ?
     LIMIT 1"
);
$pub_stmt->bind_param("i", $pub_id);
$pub_stmt->execute();
$publisher = $pub_stmt->get_result()->fetch_assoc();
$pub_stmt->close();

// Aggregate summary
$summary_stmt = $mysqli->prepare(
    "SELECT COUNT(*) AS total_clicks,
            COUNT(DISTINCT ip_address) AS distinct_ips,
            COUNT(DISTINCT browser_agent) AS distinct_agents,
            COUNT(DISTINCT referrer) AS distinct_referrers,
            SUM(CASE WHEN isaudit = 1 AND is_reject = 0 THEN 1 ELSE 0 END) AS accepted_clicks,
            SUM(CASE WHEN isaudit = 1 AND is_reject = 1 THEN 1 ELSE 0 END) AS rejected_clicks,
            SUM(CASE WHEN isaudit = 0 THEN 1 ELSE 0 END) AS pending_clicks,
            COALESCE(SUM(CASE WHEN isaudit = 1 AND is_reject = 0 THEN revenue_publishers ELSE 0 END), 0) AS accepted_revenue
     FROM ad_clicks
     WHERE pub_id = ?"
);
$summary_stmt->bind_param("i", $pub_id);
$summary_stmt->execute();
$summary = $summary_stmt->get_result()->fetch_assoc();
$summary_stmt->close();

// Recent clicks (most recent first)
$clicks_stmt = $mysqli->prepare(
    "SELECT id, ad_id, title_ads, ads_providers_name, ads_providers_domain_url,
            ip_address, browser_agent, referrer, click_time, time_epoch_click,
            isaudit, is_reject, reason_rejection, revenue_publishers
     FROM ad_clicks
     WHERE pub_id = ?
     ORDER BY id DESC
     LIMIT ?"
);
$clicks_stmt->bind_param("ii", $pub_id, $click_limit);
$clicks_stmt->execute();
$clicks_result = $clicks_stmt->get_result();

$site_host = '';
if (!empty($publisher['site_domain'])) {
    $parsed = parse_url((strpos($publisher['site_domain'], '://') !== false ? '' : 'https://') . $publisher['site_domain']);
    $site_host = strtolower($parsed['host'] ?? $publisher['site_domain']);
    $site_host = preg_replace('/^www\./', '', $site_host);
}

$ip_counts = [];
$clicks = [];
while ($row = $clicks_result->fetch_assoc()) {
    $ip_counts[$row['ip_address']] = ($ip_counts[$row['ip_address']] ?? 0) + 1;
    $clicks[] = $row;
}
$clicks_stmt->close();

$bot_ua_count = 0;
$referrer_mismatch_count = 0;
$empty_referrer_count = 0;
foreach ($clicks as &$c) {
    $c['is_bot_ua'] = is_probable_bot_user_agent($c['browser_agent']);
    if ($c['is_bot_ua']) {
        $bot_ua_count++;
    }

    $c['referrer_host'] = '';
    $c['referrer_mismatch'] = false;
    if (trim((string) $c['referrer']) === '') {
        $empty_referrer_count++;
    } else {
        $ref_parsed = parse_url($c['referrer']);
        $ref_host = strtolower($ref_parsed['host'] ?? '');
        $ref_host = preg_replace('/^www\./', '', $ref_host);
        $c['referrer_host'] = $ref_host;
        if ($site_host !== '' && $ref_host !== '' && $ref_host !== $site_host && !str_ends_with($ref_host, '.' . $site_host)) {
            $c['referrer_mismatch'] = true;
            $referrer_mismatch_count++;
        }
    }

    $c['ip_repeat_count'] = $ip_counts[$c['ip_address']] ?? 1;
}
unset($c);

// Rapid-duplicate detection: group clicks by IP+browser_agent, walk each
// group in chronological order, and flag any click that followed the
// previous one from the same IP+UA within $burst_window_seconds — the
// classic accidental-double-click / naive click-bot signature (e.g. two
// "Diakui" clicks from the same phone 5 seconds apart). This is a distinct,
// tighter signal than the plain "(xN)" IP-repeat count, which says nothing
// about timing.
$by_ip_ua = [];
foreach ($clicks as $c) {
    $by_ip_ua[$c['ip_address'] . '|' . $c['browser_agent']][] = $c;
}

$burst_any_ids = [];
$burst_accepted_ids = [];
$duplicate_followup_ids = [];
$burst_accepted_pair_count = 0;
$burst_leaked_revenue = 0.0;
foreach ($by_ip_ua as $group) {
    usort($group, fn($a, $b) => $a['time_epoch_click'] <=> $b['time_epoch_click']);
    for ($i = 1; $i < count($group); $i++) {
        $prev = $group[$i - 1];
        $curr = $group[$i];
        $delta = $curr['time_epoch_click'] - $prev['time_epoch_click'];
        if ($delta < 0 || $delta > $burst_window_seconds) {
            continue;
        }
        $burst_any_ids[$prev['id']] = true;
        $burst_any_ids[$curr['id']] = true;

        $prev_accepted = ((int) $prev['isaudit'] === 1 && (int) $prev['is_reject'] === 0);
        $curr_accepted = ((int) $curr['isaudit'] === 1 && (int) $curr['is_reject'] === 0);
        if ($prev_accepted && $curr_accepted) {
            $burst_accepted_ids[$prev['id']] = true;
            $burst_accepted_ids[$curr['id']] = true;
            if (!isset($duplicate_followup_ids[$curr['id']])) {
                $duplicate_followup_ids[$curr['id']] = true;
                $burst_accepted_pair_count++;
                $burst_leaked_revenue += (float) $curr['revenue_publishers'];
            }
        }
    }
}

foreach ($clicks as &$c) {
    $c['is_burst_any'] = isset($burst_any_ids[$c['id']]);
    $c['is_burst_accepted'] = isset($burst_accepted_ids[$c['id']]);
}
unset($c);

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forensik Klik Publisher</title>
    <?php include("style_toogle.php"); ?>
    <style>
        .forensics-toolbar { display: flex; align-items: end; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
        .pub-id-form { display: flex; gap: .5rem; }
        .pub-id-form input { width: 140px; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1rem; }
        .summary-card { padding: 1rem 1.15rem; border-radius: .7rem; background: #fff; box-shadow: 0 3px 14px rgba(31,41,55,.08); }
        .summary-label { color: #6c757d; font-size: .72rem; font-weight: 600; letter-spacing: .035em; text-transform: uppercase; }
        .summary-value { margin-top: .2rem; color: #25313c; font-size: 1.2rem; font-weight: 700; }
        .summary-value.warn { color: #dc3545; }
        .summary-value.ok { color: #198754; }
        .flag-bad { color: #dc3545; font-weight: 700; }
        .flag-ok { color: #198754; }
        .ua-cell, .referrer-cell { max-width: 260px; overflow-wrap: anywhere; font-size: .78rem; }
        .status-badge { display: inline-block; padding: .15rem .5rem; border-radius: 99px; font-size: .72rem; font-weight: 700; }
        .status-accepted { background: #e6f7ee; color: #198754; }
        .status-rejected { background: #fdeaea; color: #dc3545; }
        .status-pending { background: #fff6e0; color: #997404; }
        .pub-info-card { padding: 1rem 1.25rem; border-radius: .7rem; background: #fff; box-shadow: 0 3px 14px rgba(31,41,55,.08); margin-bottom: 1rem; }
        @media (max-width: 991.98px) { .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    </style>
</head>
<body>
<div class="admin-navbar">
    <a class="brand" href="dashboard_admin.php">Admin Dashboard</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
</div>

<?php include("sidebar_menu.php"); ?>

<main class="admin-main" id="mainContent">
    <div class="forensics-toolbar mb-4">
        <div>
            <h1 class="page-title">Forensik Klik Publisher</h1>
            <p class="page-subtitle">Investigasi pola klik satu publisher: reuse IP/UA, bot UA, referrer yang tidak cocok domain, sampai <?php echo $click_limit; ?> klik terbaru.</p>
        </div>
        <form method="get" action="publisher_click_forensics.php" class="pub-id-form">
            <input type="number" name="pub_id" class="form-control" value="<?php echo (int) $pub_id; ?>" placeholder="Publisher ID">
            <input type="number" name="burst_window" class="form-control" value="<?php echo (int) $burst_window_seconds; ?>" placeholder="Jendela duplikat (detik)" title="Jendela waktu (detik) untuk deteksi klik duplikat cepat dari IP+UA yang sama">
            <button class="btn btn-primary" type="submit"><i class="fas fa-search mr-1"></i> Cek</button>
        </form>
    </div>

    <?php if (!$publisher): ?>
        <div class="card"><div class="card-body text-center text-muted py-4">Publisher #<?php echo (int) $pub_id; ?> tidak ditemukan.</div></div>
    <?php else: ?>
        <div class="pub-info-card">
            <strong><?php echo htmlspecialchars($publisher['site_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></strong>
            &middot; <a href="<?php echo htmlspecialchars($publisher['site_domain'] ?: '#', ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($publisher['site_domain'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></a>
            &middot; Publisher #<?php echo (int) $publisher['id']; ?>
            &middot; Terdaftar <?php echo htmlspecialchars($publisher['regdate'] ?: '-', ENT_QUOTES, 'UTF-8'); ?>
            <br>
            Pemilik:
            <?php if (!empty($publisher['owner_email'])): ?>
                <a href="manage_users.php?search=<?php echo urlencode($publisher['owner_email']); ?>"><?php echo htmlspecialchars($publisher['owner_email'], ENT_QUOTES, 'UTF-8'); ?></a>
            <?php else: ?>
                <span class="text-muted">tidak ditemukan</span>
            <?php endif; ?>
        </div>

        <div class="summary-grid">
            <div class="summary-card"><div class="summary-label">Total Klik</div><div class="summary-value"><?php echo number_format((int) $summary['total_clicks'], 0, ',', '.'); ?></div></div>
            <div class="summary-card"><div class="summary-label">Diakui (Revenue Aman)</div><div class="summary-value ok"><?php echo number_format((int) $summary['accepted_clicks'], 0, ',', '.'); ?></div></div>
            <div class="summary-card"><div class="summary-label">Ditolak</div><div class="summary-value"><?php echo number_format((int) $summary['rejected_clicks'], 0, ',', '.'); ?></div></div>
            <div class="summary-card"><div class="summary-label">Belum Diaudit</div><div class="summary-value"><?php echo number_format((int) $summary['pending_clicks'], 0, ',', '.'); ?></div></div>

            <div class="summary-card"><div class="summary-label">IP Unik</div><div class="summary-value"><?php echo number_format((int) $summary['distinct_ips'], 0, ',', '.'); ?></div></div>
            <div class="summary-card"><div class="summary-label">User-Agent Unik</div><div class="summary-value"><?php echo number_format((int) $summary['distinct_agents'], 0, ',', '.'); ?></div></div>
            <div class="summary-card"><div class="summary-label">UA Terindikasi Bot (dari <?php echo count($clicks); ?> ditampilkan)</div><div class="summary-value <?php echo $bot_ua_count > 0 ? 'warn' : 'ok'; ?>"><?php echo number_format($bot_ua_count, 0, ',', '.'); ?></div></div>
            <div class="summary-card"><div class="summary-label">Referrer Tidak Cocok Domain</div><div class="summary-value <?php echo $referrer_mismatch_count > 0 ? 'warn' : 'ok'; ?>"><?php echo number_format($referrer_mismatch_count, 0, ',', '.'); ?></div></div>

            <div class="summary-card"><div class="summary-label">Referrer Kosong</div><div class="summary-value <?php echo $empty_referrer_count > 0 ? 'warn' : 'ok'; ?>"><?php echo number_format($empty_referrer_count, 0, ',', '.'); ?></div></div>
            <div class="summary-card"><div class="summary-label">Revenue Diakui</div><div class="summary-value ok">Rp <?php echo number_format((float) $summary['accepted_revenue'], 2, ',', '.'); ?></div></div>

            <div class="summary-card"><div class="summary-label">Pasang Klik Duplikat Cepat (≤<?php echo (int) $burst_window_seconds; ?>d, Diakui)</div><div class="summary-value <?php echo $burst_accepted_pair_count > 0 ? 'warn' : 'ok'; ?>"><?php echo number_format($burst_accepted_pair_count, 0, ',', '.'); ?></div></div>
            <div class="summary-card"><div class="summary-label">Estimasi Revenue Duplikat</div><div class="summary-value <?php echo $burst_leaked_revenue > 0 ? 'warn' : 'ok'; ?>">Rp <?php echo number_format($burst_leaked_revenue, 2, ',', '.'); ?></div></div>
        </div>

        <?php if ($site_host === ''): ?>
        <div class="alert alert-warning">Domain publisher ini belum diisi (<code>site_domain</code> kosong) — kolom "Referrer Tidak Cocok Domain" tidak bisa dihitung akurat.</div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <?php echo count($clicks); ?> klik terbaru (domain publisher terdaftar: <code><?php echo htmlspecialchars($site_host ?: '-', ENT_QUOTES, 'UTF-8'); ?></code>)
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Waktu</th>
                                <th>Iklan</th>
                                <th>IP (jumlah muncul)</th>
                                <th>User-Agent</th>
                                <th>Referrer</th>
                                <th>Status</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($clicks as $c): ?>
                            <tr<?php echo $c['is_burst_accepted'] ? ' class="table-danger"' : ($c['is_burst_any'] ? ' class="table-warning"' : ''); ?>>
                                <td>#<?php echo (int) $c['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($c['click_time'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ($c['is_burst_accepted']): ?><br><span class="flag-bad">&#9888; duplikat cepat (diakui)</span><?php elseif ($c['is_burst_any']): ?><br><span class="flag-bad">&#9888; duplikat cepat</span><?php endif; ?>
                                </td>
                                <td class="ua-cell">
                                    <strong><?php echo htmlspecialchars($c['title_ads'] ?: '(tanpa judul)', ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <br><small class="text-muted">Ad #<?php echo (int) $c['ad_id']; ?></small>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($c['ads_providers_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($c['ip_address'] ?: '-', ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ($c['ip_repeat_count'] > 1): ?><span class="flag-bad">(&times;<?php echo (int) $c['ip_repeat_count']; ?>)</span><?php endif; ?>
                                </td>
                                <td class="ua-cell">
                                    <?php echo htmlspecialchars($c['browser_agent'] ?: '(kosong)', ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ($c['is_bot_ua']): ?><br><span class="flag-bad">&#9888; terindikasi bot</span><?php endif; ?>
                                </td>
                                <td class="referrer-cell">
                                    <?php echo htmlspecialchars($c['referrer'] ?: '(kosong)', ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ($c['referrer_mismatch']): ?><br><span class="flag-bad">&#9888; host tidak cocok</span><?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int) $c['isaudit'] === 1 && (int) $c['is_reject'] === 0): ?>
                                        <span class="status-badge status-accepted">Diakui</span>
                                    <?php elseif ((int) $c['isaudit'] === 1 && (int) $c['is_reject'] === 1): ?>
                                        <span class="status-badge status-rejected">Ditolak</span>
                                        <?php if (!empty($c['reason_rejection'])): ?><br><small class="text-muted"><?php echo htmlspecialchars($c['reason_rejection'], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>Rp <?php echo number_format((float) $c['revenue_publishers'], 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($clicks)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada klik untuk publisher ini.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php
include("footer.php");
include("js_toogle.php");
?>
</body>
</html>
