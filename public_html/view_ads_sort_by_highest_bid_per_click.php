<?php

// view_ads_sort_by_highest_bid_per_click.php
include("db.php");
include("function.php");

session_start();

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Assuming get_providers_domain_url is a helper function to retrieve domain URL for the provider
$this_providers_id = 1;
//$this_providers_domain_url = get_providers_domain_url($conn, $this_providers_id);

$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);

// SQL query to fetch ads sorted by highest bid per click
$sql = "
(
  SELECT 
    local_ads_id,
    providers_name,
    providers_domain_url,
    advertisers_id,
    title_ads,
    landingpage_ads, 
    budget_per_click_textads,
    'advertisers_ads' AS source_table
  FROM 
    advertisers_ads
  WHERE 
    ispublished = 1 
    AND is_paused = 0
)
UNION
(
  SELECT 
    local_ads_id,
    providers_name,
    providers_domain_url,
    advertisers_id,
    title_ads,
    landingpage_ads, 
    budget_per_click_textads,
    'advertisers_ads_partners' AS source_table
  FROM 
    advertisers_ads_partners
  WHERE 
    ispublished = 1 
    AND is_paused = 0
)
ORDER BY budget_per_click_textads DESC
LIMIT 0, 100
";

// Execute the query
$result = $conn->query($sql);

$ads = [];
$local_count = 0;
$partner_count = 0;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $ads[] = $row;
        if ($row['source_table'] === 'advertisers_ads') {
            $local_count++;
        } else {
            $partner_count++;
        }
    }
}
$highest_bid = !empty($ads) ? (float) $ads[0]['budget_per_click_textads'] : 0;

// Start HTML with Bootstrap integration
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ads by Highest Bid Per Click</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; color: #26313c; }
        .page-shell { max-width: 1240px; }
        .page-heading h1 { margin: 0; font-size: 1.65rem; font-weight: 750; }
        .page-heading p { margin: .3rem 0 0; color: #6c757d; }
        .stat-card { border: 0; border-radius: .85rem; box-shadow: 0 3px 14px rgba(31,41,55,.07); }
        .stat-label { display: block; color: #6c757d; font-size: .72rem; font-weight: 700; letter-spacing: .035em; text-transform: uppercase; }
        .stat-value { display: block; margin-top: .2rem; font-size: 1.35rem; font-weight: 750; }
        .ads-grid { counter-reset: ad-rank; }
        .ads-grid > div { counter-increment: ad-rank; }
        .ad-card { overflow: hidden; border: 0; border-radius: .9rem; box-shadow: 0 3px 14px rgba(31,41,55,.08); transition: transform .2s ease, box-shadow .2s ease; }
        .ad-card:hover { transform: translateY(-3px); box-shadow: 0 7px 22px rgba(31,41,55,.12); }
        .ad-card::before { content: '#' counter(ad-rank); position: absolute; top: .85rem; left: .9rem; z-index: 1; padding: .25rem .5rem; border-radius: 2rem; background: #172033; color: #fff; font-size: .72rem; font-weight: 700; }
        .ad-card .card-body { display: flex; flex-direction: column; padding-top: 3.2rem; }
        .ad-title { min-height: 2.8rem; font-size: 1.05rem; line-height: 1.35; }
        .bid-box { margin: 1rem 0; padding: .85rem; border-radius: .7rem; background: #eaf8f0; color: #116538; }
        .bid-label { display: block; font-size: .7rem; font-weight: 700; text-transform: uppercase; }
        .bid-value { font-size: 1.35rem; font-weight: 800; }
        .ad-details { display: grid; gap: .45rem; margin-bottom: 1rem; }
        .ad-detail { display: flex; justify-content: space-between; gap: 1rem; padding-bottom: .4rem; border-bottom: 1px dashed #e2e7ec; font-size: .82rem; }
        .ad-detail span:last-child { max-width: 65%; text-align: right; overflow-wrap: anywhere; }
        .source-badge { position: absolute; top: .85rem; right: .9rem; }
        @media (max-width: 575.98px) { .page-shell { padding-right: .75rem; padding-left: .75rem; } .page-heading h1 { font-size: 1.4rem; } }
    </style>
</head>
<body>

<div class="container page-shell py-3 py-md-4">
    <?php include("main_menu.php") ?>
        <?php include("include_publisher_menu.php") ?>

    <div class="page-heading mb-4">
        <h1>Iklan dengan Bid Tertinggi</h1>
        <p>Daftar iklan aktif, diurutkan berdasarkan nilai pembayaran per klik tertinggi.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="card stat-card h-100"><div class="card-body"><span class="stat-label">Total Iklan</span><span class="stat-value"><?php echo number_format(count($ads)); ?></span></div></div></div>
        <div class="col-6 col-lg-3"><div class="card stat-card h-100"><div class="card-body"><span class="stat-label">Bid Tertinggi</span><span class="stat-value text-success">Rp <?php echo number_format($highest_bid, 0, ',', '.'); ?></span></div></div></div>
        <div class="col-6 col-lg-3"><div class="card stat-card h-100"><div class="card-body"><span class="stat-label">Iklan Lokal</span><span class="stat-value"><?php echo number_format($local_count); ?></span></div></div></div>
        <div class="col-6 col-lg-3"><div class="card stat-card h-100"><div class="card-body"><span class="stat-label">Iklan Partner</span><span class="stat-value"><?php echo number_format($partner_count); ?></span></div></div></div>
    </div>

    <?php if (!empty($ads)): ?>
        <div class="row g-4 ads-grid">
            <?php foreach ($ads as $row):
                $is_local = $row['source_table'] === 'advertisers_ads';
            ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <article class="card ad-card h-100 position-relative">
                        <span class="badge source-badge <?php echo $is_local ? 'text-bg-primary' : 'text-bg-warning'; ?>"><?php echo $is_local ? 'Lokal' : 'Partner'; ?></span>
                        <div class="card-body">
                            <h2 class="ad-title fw-bold"><?php echo htmlspecialchars($row['title_ads']); ?></h2>
                            <div class="bid-box">
                                <span class="bid-label">Budget per Klik</span>
                                <span class="bid-value">Rp <?php echo number_format((float) $row['budget_per_click_textads'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="ad-details">
                                <div class="ad-detail"><span>Ad ID</span><strong><?php echo htmlspecialchars($row['local_ads_id']); ?></strong></div>
                                <div class="ad-detail"><span>Advertiser ID</span><strong><?php echo htmlspecialchars($row['advertisers_id']); ?></strong></div>
                                <div class="ad-detail"><span>Provider</span><span><?php echo htmlspecialchars($row['providers_name']); ?></span></div>
                                <div class="ad-detail"><span>Domain</span><span><?php echo htmlspecialchars($row['providers_domain_url']); ?></span></div>
                            </div>
                            <a class="btn btn-outline-primary mt-auto" href="<?php echo htmlspecialchars($row['landingpage_ads'], ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt me-1"></i> Buka Landing Page</a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">Belum ada iklan aktif.</div>
    <?php endif; ?>
    <?php $conn->close(); ?>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
