<?php

// mysite_ads.php

// Include database connection and necessary functions
include("db.php");
include("function.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];


// Check if a success message is set in the query string
$status_message = isset($_GET['status']) && $_GET['status'] === 'success';



// Create a connection to the MySQL database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check the connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get provider's domain URL
$this_providers_id = 1;
//$this_providers_domain_url = get_providers_domain_url($mysqli, $this_providers_id);


$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);


$publisher_site_local_id = isset($_GET['publisher_site_local_id']) ? (int) $_GET['publisher_site_local_id'] : 0;

// Query to get site information from publishers_site table
$sql_site = "SELECT id, site_name, site_domain, site_desc ,rate_text_ads, advertiser_allowed , advertiser_rejected
             FROM publishers_site 
             WHERE id = ? 
             AND providers_domain_url = ?
            AND publishers_local_id = ?
             ";

$stmt_site = $mysqli->prepare($sql_site);
$stmt_site->bind_param("isi", $publisher_site_local_id, $this_providers_domain_url, $user_id);
$stmt_site->execute();
$site_result = $stmt_site->get_result();

// Fetch site data
if ($site_result->num_rows > 0) {
    $site_data = $site_result->fetch_assoc();
} else {
    echo '<div class="alert alert-warning text-center">No site information found.</div>';
}


// Query to get ad details from mapping_advertisers_ads_publishers_site table
// Prepare the SQL query
$sql = "SELECT id as id_mapping, title_ads, description_ads, landingpage_ads, 
               is_published, is_paused, is_expired, 
               is_approved_by_publisher, is_approved_by_advertiser, 
               rate_text_ads, budget_per_click_textads  , ads_providers_domain_url as source_advertisement_domain ,     publishers_site_local_id , site_domain
        FROM mapping_advertisers_ads_publishers_site 
        WHERE publishers_site_local_id = ? 
        AND pubs_providers_domain_url = ?
        AND  publishers_local_id = ?
        AND is_published = 1
         AND is_expired = 0
        ";

// Prepare the statement
$stmt = $mysqli->prepare($sql);

// Bind the user ID and provider domain URL
$stmt->bind_param("isi", $publisher_site_local_id, $this_providers_domain_url, $user_id);

//echo "<br>sql: ".$sql;
//echo "<br>publisher_site_local_id: ".$publisher_site_local_id;
//echo "<br>this_providers_domain_url: ".$this_providers_domain_url;


// Execute the statement
$stmt->execute();

// Get the result
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ad Details</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fa; color: #26313c; }
        .page-shell { max-width: 1240px; }
        .app-main-menu, .publisher-menu-panel { margin-bottom: 1.25rem; padding: 1.25rem; border: 1px solid #e6eaf0; border-radius: 1rem; background: #fff; box-shadow: 0 3px 14px rgba(31,41,55,.06); }
        .app-main-menu { text-align: center; }
        .app-main-menu__welcome { display: flex; flex-wrap: wrap; justify-content: center; gap: .35rem; margin-bottom: .5rem; }
        .app-main-menu__whatsapp { display: inline-block; margin-bottom: 1rem; text-decoration: none; }
        .app-menu-actions, .publisher-menu-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: .6rem; }
        .app-menu-actions .btn, .publisher-menu-grid .btn { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; min-height: 42px; border-radius: .55rem; }
        .publisher-menu-panel { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        .publisher-menu-group h2 { margin: 0 0 .85rem; text-align: center; font-size: 1.05rem; font-weight: 700; }
        .publisher-menu-grid .btn { flex: 1 1 150px; font-size: .88rem; }
        .section-heading { margin-bottom: 1rem; }
        .section-heading h1 { margin: 0; font-size: 1.6rem; font-weight: 700; }
        .section-heading p { margin: .25rem 0 0; color: #6c757d; }
        .site-card, .ad-card { border: 0; border-radius: .9rem; box-shadow: 0 3px 14px rgba(31,41,55,.07); }
        .site-card__header { padding: 1.25rem; border-bottom: 1px solid #edf0f3; }
        .site-card__header h2 { margin: 0; font-size: 1.35rem; }
        .site-details { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
        .site-detail { padding: .9rem; border-radius: .65rem; background: #f8f9fa; overflow-wrap: anywhere; }
        .site-detail__label { display: block; margin-bottom: .25rem; color: #6c757d; font-size: .75rem; font-weight: 700; text-transform: uppercase; }
        .ad-card .card-body { display: flex; flex-direction: column; }
        .ad-description { color: #5f6b76; }
        .ad-metrics { display: grid; gap: .45rem; margin: .5rem 0 1rem; }
        .ad-metric { display: flex; justify-content: space-between; gap: 1rem; padding-bottom: .4rem; border-bottom: 1px dashed #e4e7eb; font-size: .88rem; }
        .status-list { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: auto; }
        @media (max-width: 991.98px) { .publisher-menu-panel { grid-template-columns: 1fr; } .site-details { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 575.98px) {
            .page-shell { padding-right: .75rem; padding-left: .75rem; }
            .app-main-menu, .publisher-menu-panel { padding: .9rem; border-radius: .75rem; }
            .app-menu-actions .btn, .publisher-menu-grid .btn { flex: 1 1 calc(50% - .6rem); font-size: .82rem; }
            .site-details { grid-template-columns: 1fr; }
            .section-heading h1 { font-size: 1.35rem; }
        }
    </style>
</head>
<body>

<div class="container page-shell py-3 py-md-4">
 <?php include("main_menu.php"); ?>
    <?php include("include_publisher_menu.php"); ?>

    <?php if ($status_message): ?>
        <div class="alert alert-success" role="alert">Status persetujuan berhasil diperbarui.</div>
    <?php endif; ?>

 <div class="section-heading">
    <h1>Informasi Site</h1>
    <p>Ringkasan konfigurasi dan harga iklan untuk site ini.</p>
 </div>

    <!-- Display site information -->
    <?php if (!empty($site_data)): ?>
        <div class="card site-card mb-4">
            <div class="site-card__header">
                <h2><?php echo htmlspecialchars($site_data['site_name']); ?></h2>
            </div>
            <div class="card-body">
                <?php
                  $rate_text_ads =   $site_data['rate_text_ads'];
                  $rate_text_ads_with_markup_local =   $rate_text_ads + ($rate_text_ads/2);
                  $rate_text_ads_with_markup_partner =   $rate_text_ads * 2;

                ?>
                <div class="site-details">
                    <div class="site-detail"><span class="site-detail__label">ID Site</span><strong><?php echo htmlspecialchars($site_data['id']); ?></strong></div>
                    <div class="site-detail"><span class="site-detail__label">Domain</span><a href="<?php echo htmlspecialchars($site_data['site_domain']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($site_data['site_domain']); ?></a></div>
                    <div class="site-detail"><span class="site-detail__label">Deskripsi</span><?php echo htmlspecialchars($site_data['site_desc']); ?></div>
                    <div class="site-detail"><span class="site-detail__label">Rate Publisher</span><strong>Rp <?php echo number_format((float) $site_data['rate_text_ads'], 0, ',', '.'); ?></strong></div>
                    <div class="site-detail"><span class="site-detail__label">Harga Advertiser Lokal</span><strong>Rp <?php echo number_format((float) $rate_text_ads_with_markup_local, 0, ',', '.'); ?></strong></div>
                    <div class="site-detail"><span class="site-detail__label">Harga Advertiser Partner</span><strong>Rp <?php echo number_format((float) $rate_text_ads_with_markup_partner, 0, ',', '.'); ?></strong></div>
                    <div class="site-detail"><span class="site-detail__label">Advertiser Diizinkan</span><?php echo htmlspecialchars($site_data['advertiser_allowed'] ?: '-'); ?></div>
                    <div class="site-detail"><span class="site-detail__label">Advertiser Ditolak</span><?php echo htmlspecialchars($site_data['advertiser_rejected'] ?: '-'); ?></div>
                </div>
            </div>
        </div>
    <?php endif; ?>


    <div class="section-heading mt-5">
        <h1>Daftar Iklan</h1>
        <p>Iklan aktif yang dipetakan ke site ini.</p>
    </div>
    
   <?php if ($result->num_rows > 0): ?>
        <div class="row g-4">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card ad-card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($row['title_ads']); ?></h5>
                            <p class="ad-description"><?php echo htmlspecialchars($row['description_ads']); ?></p>
                            <a class="small text-break mb-3" href="<?php echo htmlspecialchars($row['landingpage_ads']); ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt me-1"></i> Buka landing page</a>

                            <div class="ad-metrics">
                                <div class="ad-metric"><span>Site ID</span><strong><?php echo htmlspecialchars($row['publishers_site_local_id']); ?></strong></div>
                                <div class="ad-metric"><span>Domain</span><span class="text-break text-end"><?php echo htmlspecialchars($row['site_domain']); ?></span></div>
                                <div class="ad-metric"><span>Rate Publisher</span><strong>Rp <?php echo number_format((float) $row['rate_text_ads'], 0, ',', '.'); ?></strong></div>
                                <div class="ad-metric"><span>Budget per Klik</span><strong>Rp <?php echo number_format((float) $row['budget_per_click_textads'], 0, ',', '.'); ?></strong></div>
                                <div class="ad-metric"><span>Provider</span><span class="text-break text-end"><?php echo htmlspecialchars($row['source_advertisement_domain']); ?></span></div>
                                <div class="ad-metric"><span>Persetujuan Advertiser</span><strong><?php echo $row['is_approved_by_advertiser'] ? 'Ya' : 'Tidak'; ?></strong></div>
                            </div>

                            <div class="status-list mb-3">
                                <span class="badge <?php echo $row['is_published'] ? 'text-bg-success' : 'text-bg-secondary'; ?>"><?php echo $row['is_published'] ? 'Published' : 'Unpublished'; ?></span>
                                <span class="badge <?php echo $row['is_paused'] ? 'text-bg-warning' : 'text-bg-light border'; ?>"><?php echo $row['is_paused'] ? 'Paused' : 'Berjalan'; ?></span>
                                <span class="badge <?php echo $row['is_expired'] ? 'text-bg-danger' : 'text-bg-info'; ?>"><?php echo $row['is_expired'] ? 'Expired' : 'Aktif'; ?></span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between gap-2 mt-auto">
                                <span class="small"><strong>Persetujuan Anda:</strong> <?php echo $row['is_approved_by_publisher'] ? 'Ya' : 'Tidak'; ?></span>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal" 
                                        data-id="<?php echo $row['id_mapping']; ?>" 
                                        data-is_approved_by_publisher="<?php echo $row['is_approved_by_publisher']; ?>">
                                    <i class="fas fa-edit me-1"></i> Ubah
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center">No ads found.</div>
    <?php endif; ?>

</div>

<!-- Modal for editing 'is_approved_by_publisher' -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="update_ad.php">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Approval by Publisher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Hidden fields to send data -->
                <input type="hidden" id="ad_id" name="ad_id">
                <input type="hidden" id="publisher_site_local_id" name="publisher_site_local_id">

                <div class="mb-3">
                    <label for="is_approved_by_publisher" class="form-label">Is Approved by Publisher</label>
                    <select class="form-select" id="is_approved_by_publisher" name="is_approved_by_publisher">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </form>
  </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var editModal = document.getElementById('editModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var adId = button.getAttribute('data-id');
            var isApprovedByPublisher = button.getAttribute('data-is_approved_by_publisher');
            var publisherSiteLocalId = "<?php echo $publisher_site_local_id; ?>"; // Add this line to get publisher_site_local_id

            var modalAdId = document.getElementById('ad_id');
            var modalIsApprovedByPublisher = document.getElementById('is_approved_by_publisher');
            var modalPublisherSiteLocalId = document.getElementById('publisher_site_local_id');

            modalAdId.value = adId;
            modalIsApprovedByPublisher.value = isApprovedByPublisher;
            modalPublisherSiteLocalId.value = publisherSiteLocalId; // Set this value in hidden field
        });
    });
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>




</body>
</html>

<?php
// Close the statement and connection
$stmt->close();
$mysqli->close();
?>
