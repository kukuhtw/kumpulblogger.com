<?php
include("db.php");
include("function.php");
include("admin/function_admin.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$email = $_SESSION['email'] ?? '';
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit("Database connection failed.");
}

$provider_domain = get_providers_domain_url_json("providers_data.json", 1);

// Perbarui nilai revenue dari situs sebelum menampilkan ringkasan.
$local_revenue = (float) updateLocalRevenue($mysqli, $user_id);
$partner_revenue = (float) updateGlobalRevenue($mysqli, $user_id);
$total_revenue = $local_revenue + $partner_revenue;

// Paid diambil langsung dari catatan pembayaran agar dapat diverifikasi pengguna.
$stmt_paid_local = $mysqli->prepare("SELECT COALESCE(SUM(nominal), 0) AS total FROM payment_local_pubs WHERE email_pubs = ?");
$stmt_paid_local->bind_param("s", $email);
$stmt_paid_local->execute();
$local_paid = (float) $stmt_paid_local->get_result()->fetch_assoc()['total'];
$stmt_paid_local->close();

$stmt_paid_partner = $mysqli->prepare("SELECT COALESCE(SUM(nominal), 0) AS total FROM payment_partner_pubs_sync WHERE publisher_local_id = ? AND email_pubs = ?");
$stmt_paid_partner->bind_param("is", $user_id, $email);
$stmt_paid_partner->execute();
$partner_paid = (float) $stmt_paid_partner->get_result()->fetch_assoc()['total'];
$stmt_paid_partner->close();

$total_paid = $local_paid + $partner_paid;
$local_unpaid = max(0, $local_revenue - $local_paid);
$partner_unpaid = max(0, $partner_revenue - $partner_paid);
$total_unpaid = $local_unpaid + $partner_unpaid;

$stmt_sites = $mysqli->prepare("SELECT site_name, site_domain, current_site_revenue, current_site_revenue_from_partner
                                FROM publishers_site WHERE providers_domain_url = ? AND publishers_local_id = ?
                                ORDER BY (current_site_revenue + current_site_revenue_from_partner) DESC");
$stmt_sites->bind_param("si", $provider_domain, $user_id);
$stmt_sites->execute();
$sites = $stmt_sites->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_sites->close();

// Gabungkan pembayaran lokal dan partner dalam satu riwayat.
$stmt_payments = $mysqli->prepare("SELECT payment_type, id, nominal, payment_description, payment_date FROM (
    SELECT 'Lokal' AS payment_type, id, nominal, payment_description, payment_date
    FROM payment_local_pubs WHERE email_pubs = ?
    UNION ALL
    SELECT 'Partner' AS payment_type, id, nominal, payment_description, payment_date
    FROM payment_partner_pubs_sync WHERE publisher_local_id = ? AND email_pubs = ?
) payments ORDER BY payment_date DESC LIMIT 20");
$stmt_payments->bind_param("sis", $email, $user_id, $email);
$stmt_payments->execute();
$payments = $stmt_payments->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_payments->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendapatan Publisher</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --brand:#087f5b; --ink:#26313c; --muted:#6c757d; --bg:#f4f7f6; }
        body { margin:0; background:var(--bg); color:var(--ink); }
        .page-shell { max-width:1240px; }
        .page-title { margin:0; font-size:1.65rem; font-weight:800; }
        .page-subtitle { margin:.3rem 0 0; color:var(--muted); }
        .summary-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; margin:1.5rem 0; }
        .summary-card { padding:1.2rem; border-radius:.85rem; background:#fff; box-shadow:0 3px 14px rgba(31,65,54,.08); }
        .summary-label { color:var(--muted); font-size:.72rem; font-weight:750; letter-spacing:.04em; text-transform:uppercase; }
        .summary-value { display:block; margin-top:.25rem; font-size:1.5rem; font-weight:850; white-space:nowrap; }
        .summary-card.total .summary-value { color:#0d6efd; }
        .summary-card.paid .summary-value { color:#198754; }
        .summary-card.unpaid .summary-value { color:#dc3545; }
        .detail-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; margin-bottom:1rem; }
        .detail-card, .data-card { border:0; border-radius:.85rem; background:#fff; box-shadow:0 3px 14px rgba(31,65,54,.08); }
        .detail-card { padding:1.15rem; }
        .detail-card h2 { margin:0 0 .8rem; font-size:1rem; font-weight:800; }
        .money-row { display:flex; justify-content:space-between; gap:1rem; padding:.55rem 0; border-bottom:1px dashed #e1e8e5; }
        .money-row:last-child { border-bottom:0; }
        .money-row span { color:var(--muted); }
        .money-row strong { white-space:nowrap; }
        .data-card { margin-top:1rem; overflow:hidden; }
        .data-card-header { padding:1rem 1.2rem; border-bottom:1px solid #e7ecea; font-weight:800; }
        .data-card-body { padding:1rem; }
        .site-name { display:block; font-weight:700; }
        .site-domain { color:#0d6efd; font-size:.8rem; overflow-wrap:anywhere; }
        .amount { color:#198754; font-weight:750; white-space:nowrap; }
        .payment-description { min-width:220px; color:#52606d; }
        @media(max-width:767.98px) { .summary-grid { grid-template-columns:1fr; } .detail-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="container page-shell py-3 py-md-4">
    <?php include("main_menu.php"); ?>
    <?php include("include_publisher_menu.php"); ?>

    <header class="mt-4">
        <h1 class="page-title">Pendapatan Publisher</h1>
        <p class="page-subtitle">Ringkasan revenue dan pembayaran untuk <?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>.</p>
    </header>

    <section class="summary-grid">
        <div class="summary-card total"><span class="summary-label">Total Revenue</span><strong class="summary-value">Rp <?php echo number_format($total_revenue, 2, ',', '.'); ?></strong></div>
        <div class="summary-card paid"><span class="summary-label">Sudah Dibayar</span><strong class="summary-value">Rp <?php echo number_format($total_paid, 2, ',', '.'); ?></strong></div>
        <div class="summary-card unpaid"><span class="summary-label">Belum Dibayar</span><strong class="summary-value">Rp <?php echo number_format($total_unpaid, 2, ',', '.'); ?></strong></div>
    </section>

    <section class="detail-grid">
        <div class="detail-card"><h2>Revenue Lokal</h2><div class="money-row"><span>Total revenue</span><strong>Rp <?php echo number_format($local_revenue, 2, ',', '.'); ?></strong></div><div class="money-row"><span>Sudah dibayar</span><strong class="text-success">Rp <?php echo number_format($local_paid, 2, ',', '.'); ?></strong></div><div class="money-row"><span>Belum dibayar</span><strong class="text-danger">Rp <?php echo number_format($local_unpaid, 2, ',', '.'); ?></strong></div></div>
        <div class="detail-card"><h2>Revenue Partner</h2><div class="money-row"><span>Total revenue</span><strong>Rp <?php echo number_format($partner_revenue, 2, ',', '.'); ?></strong></div><div class="money-row"><span>Sudah dibayar</span><strong class="text-success">Rp <?php echo number_format($partner_paid, 2, ',', '.'); ?></strong></div><div class="money-row"><span>Belum dibayar</span><strong class="text-danger">Rp <?php echo number_format($partner_unpaid, 2, ',', '.'); ?></strong></div></div>
    </section>

    <section class="data-card"><div class="data-card-header">Revenue per Situs</div><div class="data-card-body"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Situs</th><th>Revenue Lokal</th><th>Revenue Partner</th><th>Total</th></tr></thead><tbody>
    <?php foreach ($sites as $site): $site_total = (float)$site['current_site_revenue'] + (float)$site['current_site_revenue_from_partner']; ?>
        <tr><td><span class="site-name"><?php echo htmlspecialchars($site['site_name'], ENT_QUOTES, 'UTF-8'); ?></span><span class="site-domain"><?php echo htmlspecialchars($site['site_domain'], ENT_QUOTES, 'UTF-8'); ?></span></td><td>Rp <?php echo number_format((float)$site['current_site_revenue'], 2, ',', '.'); ?></td><td>Rp <?php echo number_format((float)$site['current_site_revenue_from_partner'], 2, ',', '.'); ?></td><td class="amount">Rp <?php echo number_format($site_total, 2, ',', '.'); ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$sites): ?><tr><td colspan="4" class="text-center text-muted py-4">Belum ada data revenue situs.</td></tr><?php endif; ?>
    </tbody></table></div></div></section>

    <section class="data-card mb-4"><div class="data-card-header">20 Pembayaran Terbaru</div><div class="data-card-body"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Jenis</th><th>Nominal</th><th>Keterangan</th><th>Tanggal Pembayaran</th></tr></thead><tbody>
    <?php foreach ($payments as $payment): ?>
        <tr><td><span class="badge <?php echo $payment['payment_type'] === 'Lokal' ? 'text-bg-success' : 'text-bg-primary'; ?>"><?php echo htmlspecialchars($payment['payment_type'], ENT_QUOTES, 'UTF-8'); ?></span></td><td class="amount">Rp <?php echo number_format((float)$payment['nominal'], 2, ',', '.'); ?></td><td class="payment-description"><?php echo htmlspecialchars($payment['payment_description'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td><td class="text-nowrap"><?php echo htmlspecialchars($payment['payment_date'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$payments): ?><tr><td colspan="4" class="text-center text-muted py-4">Belum ada pembayaran yang tercatat.</td></tr><?php endif; ?>
    </tbody></table></div></div></section>
</div>
<?php $mysqli->close(); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
