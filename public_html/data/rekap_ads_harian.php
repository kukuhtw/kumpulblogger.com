<?php

// DATA/rekap_ads_harian.php

include("../db.php"); // Koneksi database
include("../function.php");

// Seluruh waktu pada halaman ini ditampilkan dalam zona Indonesia Barat (GMT+7).
date_default_timezone_set('Asia/Jakarta');

// Create a connection to the MySQL database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check the connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
$mysqli->query("SET time_zone = '+07:00'");

$id = 1;
$this_providers_domain_url = get_providers_domain_url($mysqli, $id);

// Fungsi untuk mengubah nama hari ke bahasa Indonesia
function getHariIndonesia($hariInggris) {
    $hari = array(
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    );
    return $hari[$hariInggris];
}

// Filter (GET, supaya bisa dipakai ulang oleh link paging)
$tanggal_klik = isset($_GET['tanggal_klik']) ? $_GET['tanggal_klik'] : 'all';
$tanggal_spesifik = isset($_GET['tanggal_spesifik']) ? $_GET['tanggal_spesifik'] : '';
$local_ads_id_ads_provider_domain = isset($_GET['local_ads_id_ads_provider_domain']) ? $_GET['local_ads_id_ads_provider_domain'] : 'all';
$sumber_data = isset($_GET['sumber_data']) ? $_GET['sumber_data'] : 'all';

// Paging
$page = max(1, isset($_GET['page']) ? (int) $_GET['page'] : 1);
$limit = 12;

// Bangun kondisi filter dengan prepared statement (mencegah SQL injection)
$conditions = [];
$params = [];
$types = '';

if ($tanggal_klik === 'specific') {
    if (!empty($tanggal_spesifik)) {
        $conditions[] = "tanggal_klik = ?";
        $params[] = $tanggal_spesifik;
        $types .= 's';
    } else {
        echo '<div class="alert alert-warning">Tanggal spesifik tidak valid!</div>';
    }
}

if (!empty($local_ads_id_ads_provider_domain) && $local_ads_id_ads_provider_domain !== 'all') {
    list($local_ads_id, $ads_providers_domain_url) = explode('~', $local_ads_id_ads_provider_domain);
    $conditions[] = "local_ads_id = ? AND ads_providers_domain_url = ?";
    $params[] = (int) $local_ads_id;
    $params[] = $ads_providers_domain_url;
    $types .= 'is';
}

if (!empty($sumber_data) && $sumber_data !== 'all') {
    $conditions[] = "sumber_data = ?";
    $params[] = $sumber_data;
    $types .= 's';
}

$where_sql = $conditions ? (" WHERE " . implode(' AND ', $conditions)) : '';

// Hitung total baris & total halaman
$count_stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM rekap_harian" . $where_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_rows = (int) $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = max(1, (int) ceil($total_rows / $limit));
$page = min($page, $total_pages);
$offset = ($page - 1) * $limit;
$window_start = max(1, $page - 2);
$window_end = min($total_pages, $page + 2);

// Grand total dihitung atas seluruh data yang difilter, bukan hanya halaman yang tampil
$agg_stmt = $mysqli->prepare(
    "SELECT
        SUM(revenue_publishers + revenue_adnetwork_local + revenue_adnetwork_partner) AS total_spending,
        SUM(revenue_adnetwork_local) AS total_local,
        SUM(revenue_adnetwork_partner) AS total_partner
     FROM rekap_harian" . $where_sql
);
if ($params) {
    $agg_stmt->bind_param($types, ...$params);
}
$agg_stmt->execute();
$agg_row = $agg_stmt->get_result()->fetch_assoc();
$grand_total_spending = (float) $agg_row['total_spending'];
$grand_total_revenue_adnetwork_local = (float) $agg_row['total_local'];
$grand_total_revenue_adnetwork_partner = (float) $agg_row['total_partner'];
$agg_stmt->close();

// Ambil data rekap dan jam-menit setiap klik valid yang membentuk rekap tersebut.
$query = "SELECT rh.*,
            CASE rh.sumber_data
                WHEN 'ad_clicks' THEN (
                    SELECT GROUP_CONCAT(DATE_FORMAT(ac.click_time, '%H:%i') ORDER BY ac.click_time SEPARATOR ', ')
                    FROM ad_clicks ac
                    WHERE DATE(ac.click_time) = rh.tanggal_klik
                      AND ac.local_ads_id = rh.local_ads_id
                      AND ac.ads_providers_domain_url = rh.ads_providers_domain_url
                      AND ac.isaudit = 1 AND ac.is_reject = 0
                )
                WHEN 'ad_clicks_partner' THEN (
                    SELECT GROUP_CONCAT(DATE_FORMAT(acp.click_time, '%H:%i') ORDER BY acp.click_time SEPARATOR ', ')
                    FROM ad_clicks_partner acp
                    WHERE DATE(acp.click_time) = rh.tanggal_klik
                      AND acp.local_ads_id = rh.local_ads_id
                      AND acp.ads_providers_domain_url = rh.ads_providers_domain_url
                      AND acp.isaudit = 1 AND acp.is_reject = 0
                )
                ELSE NULL
            END AS waktu_klik_gmt7
          FROM rekap_harian rh" . $where_sql . " ORDER BY rh.tanggal_klik DESC LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($query);
$page_params = $params;
$page_types = $types . 'ii';
$page_params[] = $limit;
$page_params[] = $offset;
$stmt->bind_param($page_types, ...$page_params);
$stmt->execute();
$result = $stmt->get_result();

// Bangun URL yang mempertahankan filter aktif, dipakai oleh link paging
function build_page_url($page_number) {
    $params = array_merge($_GET, ['page' => $page_number]);
    return '?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Harian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; color: #26313c; }
        .page-shell { max-width: 1200px; }
        .hero { padding: 1.5rem; border-radius: 1rem; background: linear-gradient(135deg, #172033, #275681); color: #fff; }
        .hero h1 { margin: 0; font-size: 1.5rem; font-weight: 750; }
        .filter-panel { padding: 1.25rem; border-radius: .85rem; background: #fff; box-shadow: 0 3px 14px rgba(31,41,55,.07); }
        .stat-card { padding: 1rem 1.25rem; border-radius: .85rem; background: #fff; box-shadow: 0 3px 14px rgba(31,41,55,.07); height: 100%; }
        .stat-label { display: block; font-size: .72rem; font-weight: 700; text-transform: uppercase; color: #6c757d; }
        .stat-value { display: block; margin-top: .25rem; font-size: 1.3rem; font-weight: 800; }
        .stat-card.primary .stat-value { color: #172033; }
        .stat-card.local .stat-value { color: #12683a; }
        .stat-card.partner .stat-value { color: #a3540f; }
        .rekap-card { overflow: hidden; border: 0; border-radius: .9rem; box-shadow: 0 3px 14px rgba(31,41,55,.08); transition: transform .2s ease, box-shadow .2s ease; }
        .rekap-card:hover { transform: translateY(-3px); box-shadow: 0 7px 22px rgba(31,41,55,.12); }
        .rekap-card .card-body { display: flex; flex-direction: column; }
        .rekap-date { font-size: .95rem; font-weight: 750; }
        .rekap-source-badge { font-size: .68rem; font-weight: 700; text-transform: uppercase; }
        .rekap-title { margin: .5rem 0 .25rem; font-size: 1rem; font-weight: 700; overflow-wrap: anywhere; }
        .rekap-landing { display: block; margin-bottom: .75rem; font-size: .78rem; overflow-wrap: anywhere; }
        .click-times { margin-bottom: .75rem; padding: .65rem .75rem; border: 1px solid #dbeafe; border-radius: .6rem; background: #eff6ff; }
        .click-times-label { display: block; margin-bottom: .2rem; color: #1d4ed8; font-size: .67rem; font-weight: 800; letter-spacing: .025em; text-transform: uppercase; }
        .click-times-value { color: #1e3a5f; font-size: .82rem; font-weight: 650; line-height: 1.5; overflow-wrap: anywhere; }
        .metric-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; margin-top: auto; }
        .metric-box { padding: .65rem; border-radius: .6rem; background: #f3f6f8; }
        .metric-label { display: block; font-size: .65rem; font-weight: 700; text-transform: uppercase; color: #6c757d; }
        .metric-value { display: block; margin-top: .15rem; font-size: .92rem; font-weight: 800; }
        .publisher-pagination { flex-wrap: wrap; gap: .25rem; }
        .publisher-pagination .page-link { min-width: 38px; border-radius: .45rem !important; text-align: center; }
        @media (max-width: 575.98px) { .page-shell { padding-right: .75rem; padding-left: .75rem; } .hero { padding: 1.1rem; } .hero h1 { font-size: 1.2rem; } }
    </style>
</head>

<body>
    <div class="container page-shell py-4 py-md-5">
        <div class="hero mb-4">
            <h1>Rekap Harian Transaksi Klik &mdash; <?php echo htmlspecialchars($this_providers_domain_url); ?></h1>
        </div>

        <!-- Form untuk filter -->
        <form method="get" action="" class="filter-panel mb-4 row g-3 align-items-end">
            <div class="col-12 col-md-3">
                <label for="tanggal_klik" class="form-label">Tanggal Klik</label>
                <select name="tanggal_klik" id="tanggal_klik" class="form-select">
                    <option value="all" <?php echo ($tanggal_klik == 'all') ? 'selected' : ''; ?>>Semua Tanggal</option>
                    <option value="specific" <?php echo ($tanggal_klik == 'specific') ? 'selected' : ''; ?>>Pilih Tanggal Spesifik</option>
                </select>
            </div>

            <div class="col-12 col-md-3" id="tanggal_spesifik_wrapper" style="display: <?php echo ($tanggal_klik == 'specific') ? 'block' : 'none'; ?>;">
                <label for="tanggal_spesifik" class="form-label">Tanggal Spesifik</label>
                <input type="date" name="tanggal_spesifik" id="tanggal_spesifik" class="form-control" value="<?php echo htmlspecialchars($tanggal_spesifik); ?>">
            </div>

            <div class="col-12 col-md-3">
                <label for="local_ads_id" class="form-label">ID Iklan</label>
                <select name="local_ads_id_ads_provider_domain" id="local_ads_id" class="form-select">
                    <option value="all" <?php echo ($local_ads_id_ads_provider_domain == 'all') ? 'selected' : ''; ?>>Semua ID Iklan</option>
                    <?php
                    // Ambil semua local_ads_id dari database
                    $ads_query = "SELECT DISTINCT local_ads_id, ads_providers_domain_url, title_ads FROM rekap_harian";
                    $ads_result = $mysqli->query($ads_query);
                    while ($row = $ads_result->fetch_assoc()) {
                        $value = $row['local_ads_id'] . "~" . $row['ads_providers_domain_url'];
                        $selected = ($local_ads_id_ads_provider_domain == $value) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($value) . '" ' . $selected . '>' . htmlspecialchars($row['local_ads_id'] . ' - ' . $row['title_ads'] . ' - ' . $row['ads_providers_domain_url']) . '</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="col-12 col-md-2">
                <label for="sumber_data" class="form-label">Sumber Klik</label>
                <select name="sumber_data" id="sumber_data" class="form-select">
                    <option value="all" <?php echo ($sumber_data == 'all') ? 'selected' : ''; ?>>Semua</option>
                    <option value="ad_clicks" <?php echo ($sumber_data == 'ad_clicks') ? 'selected' : ''; ?>>Lokal (ad_clicks)</option>
                    <option value="ad_clicks_partner" <?php echo ($sumber_data == 'ad_clicks_partner') ? 'selected' : ''; ?>>Partner (ad_clicks_partner)</option>
                </select>
            </div>

            <div class="col-12 col-md-1">
                <button type="submit" class="btn btn-primary w-100">Cari</button>
            </div>
        </form>

        <!-- Tampilkan hasil pencarian jika ada -->
        <?php if (isset($result) && $result->num_rows > 0): ?>

            <p class="text-muted small">Menampilkan halaman <?php echo $page; ?> dari <?php echo $total_pages; ?> &middot; <?php echo number_format($total_rows); ?> data</p>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4 mb-4">
                <?php while ($row = $result->fetch_assoc()):
                    // Gabungkan Hari dan Tanggal
                    $tanggal = date('d-m-Y', strtotime($row['tanggal_klik']));
                    $hari = getHariIndonesia(date('l', strtotime($row['tanggal_klik'])));

                    $local_ads_id = $row['local_ads_id'];
                    $ads_providers_domain_url = $row['ads_providers_domain_url'];

                    $revenue_publishers = $row['revenue_publishers'];
                    $spending_revenue_adnetwork_local = $row['revenue_adnetwork_local'];
                    $spending_revenue_adnetwork_partner = $row['revenue_adnetwork_partner'];

                    $total_spending = $revenue_publishers + $spending_revenue_adnetwork_local + $spending_revenue_adnetwork_partner;

                    $sumber_label = ($row['sumber_data'] === 'ad_clicks') ? 'Local' : 'Partner';
                    $sumber_badge_class = ($row['sumber_data'] === 'ad_clicks') ? 'bg-success' : 'bg-warning text-dark';
                    ?>
                    <div class="col">
                        <div class="card rekap-card h-100">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start">
                                    <span class="rekap-date"><?php echo htmlspecialchars("$hari, $tanggal"); ?></span>
                                    <span class="badge rounded-pill rekap-source-badge <?php echo $sumber_badge_class; ?>"><?php echo $sumber_label; ?></span>
                                </div>
                                <h2 class="rekap-title"><?php echo htmlspecialchars($row['title_ads']); ?></h2>
                                <a class="rekap-landing" href="<?php echo htmlspecialchars($row['landingpage_ads']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($row['landingpage_ads']); ?></a>

                                <div class="click-times">
                                    <span class="click-times-label">Jam Klik (GMT+7 / WIB)</span>
                                    <span class="click-times-value"><?php echo htmlspecialchars($row['waktu_klik_gmt7'] ?: 'Waktu tidak tersedia', ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>

                                <div class="metric-grid">
                                    <div class="metric-box">
                                        <span class="metric-label">Local Ads ID</span>
                                        <span class="metric-value"><?php echo htmlspecialchars($row['local_ads_id']); ?></span>
                                    </div>
                                    <div class="metric-box">
                                        <span class="metric-label">Jumlah Klik</span>
                                        <span class="metric-value"><?php echo htmlspecialchars($row['jumlah_klik']); ?></span>
                                    </div>
                                    <div class="metric-box">
                                        <span class="metric-label">Total Spending</span>
                                        <span class="metric-value">Rp <?php echo number_format($row['total_spending']); ?></span>
                                    </div>
                                    <div class="metric-box">
                                        <span class="metric-label">Budget Allocation</span>
                                        <span class="metric-value">Rp <?php echo number_format($row['budget_allocation'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <nav class="mb-4" aria-label="Navigasi rekap harian">
                <ul class="pagination publisher-pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="<?php echo htmlspecialchars(build_page_url(max(1, $page - 1))); ?>">&laquo;</a></li>
                    <?php if ($window_start > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?php echo htmlspecialchars(build_page_url(1)); ?>">1</a></li>
                        <?php if ($window_start > 2): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($i = $window_start; $i <= $window_end; $i++): ?>
                        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>"><a class="page-link" href="<?php echo htmlspecialchars(build_page_url($i)); ?>"><?php echo $i; ?></a></li>
                    <?php endfor; ?>
                    <?php if ($window_end < $total_pages): ?>
                        <?php if ($window_end < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
                        <li class="page-item"><a class="page-link" href="<?php echo htmlspecialchars(build_page_url($total_pages)); ?>"><?php echo $total_pages; ?></a></li>
                    <?php endif; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"><a class="page-link" href="<?php echo htmlspecialchars(build_page_url(min($total_pages, $page + 1))); ?>">&raquo;</a></li>
                </ul>
            </nav>

            <div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
                <div class="col">
                    <div class="stat-card primary">
                        <span class="stat-label">Grand Total Spending</span>
                        <span class="stat-value">Rp <?php echo number_format($grand_total_spending); ?></span>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card local">
                        <span class="stat-label">Grand Total AdNetwork Local</span>
                        <span class="stat-value">Rp <?php echo number_format($grand_total_revenue_adnetwork_local); ?></span>
                    </div>
                </div>
                <div class="col">
                    <div class="stat-card partner">
                        <span class="stat-label">Grand Total AdNetwork Partner</span>
                        <span class="stat-value">Rp <?php echo number_format($grand_total_revenue_adnetwork_partner); ?></span>
                    </div>
                </div>
            </div>

        <?php elseif (isset($result)): ?>
            <p class="text-center">Tidak ada data yang ditemukan.</p>
        <?php endif; ?>

        <p class="text-center text-muted small">
            <a href="../reg.php">Daftar</a> |
            <a href="../login.php">Login</a> |
            <a href="../forgot_password.php">Lupa Password?</a> |
            <a href="../index.php">Home</a>
        </p>
    </div>

    <script>
        document.getElementById('tanggal_klik').addEventListener('change', function () {
            document.getElementById('tanggal_spesifik_wrapper').style.display = (this.value === 'specific') ? 'block' : 'none';
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.min.js"></script>
</body>

</html>
