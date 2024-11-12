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
if (isset($_GET['status']) && $_GET['status'] == 'success') {
    echo '<div class="alert alert-success" role="alert">
            Approval/Rejection Status has been successfully updated!
          </div>';
}



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


$publisher_site_local_id = (int)$_GET['publisher_site_local_id'];

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
</head>
<body>

<div class="container mt-5">
 <?php include("main_menu.php"); ?>
    <?php include("include_publisher_menu.php"); ?>

 <h1 class="text-center mb-4">site information</h1>

    <!-- Display site information -->
    <?php if (!empty($site_data)): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title"><?php echo htmlspecialchars($site_data['site_name']); ?></h4>


        <p><strong>ID:</strong> <?php echo htmlspecialchars($site_data['id']); ?></p>
             
           
                <p><strong>Domain:</strong> <a href="<?php echo htmlspecialchars($site_data['site_domain']); ?>" target="_blank"><?php echo htmlspecialchars($site_data['site_domain']); ?></a></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($site_data['site_desc']); ?></p>
                <?php
                  $rate_text_ads =   $site_data['rate_text_ads'];
                  $rate_text_ads_with_markup_local =   $rate_text_ads + ($rate_text_ads/2);
                  $rate_text_ads_with_markup_partner =   $rate_text_ads * 2;

                ?>
                <p><strong>rate_text_ads:Rp </strong> <?php echo htmlspecialchars($site_data['rate_text_ads']); ?>
                <p><strong>Harga jual Advertiser local: Rp </strong> <?php echo htmlspecialchars($rate_text_ads_with_markup_local); ?>
                <p><strong>Harga jual Advertiser Partner: Rp </strong> <?php echo htmlspecialchars($rate_text_ads_with_markup_partner); ?>
            </p>
                <p><strong>advertiser_allowed:</strong> <?php echo htmlspecialchars($site_data['advertiser_allowed']); ?></p>
                
                <p><strong>advertiser_rejected:</strong> <?php echo htmlspecialchars($site_data['advertiser_rejected']); ?></p>
            </div>
        </div>
    <?php endif; ?>


    <h1 class="text-center mb-4">Ad Details</h1>
    
   <?php if ($result->num_rows > 0): ?>
        <div class="row">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($row['title_ads']); ?></h5>
                            <p class="card-text"><strong>Description:</strong> <?php echo htmlspecialchars($row['description_ads']); ?></p>
                            <p><strong>Landing Page:</strong> <a href="<?php echo htmlspecialchars($row['landingpage_ads']); ?>" target="_blank"><?php echo htmlspecialchars($row['landingpage_ads']); ?></a></p>
                           
                           <p><strong>publishers_site_local_id:</strong> <?php echo htmlspecialchars($row['publishers_site_local_id']); ?></p>   

                           <p><strong>site_domain:</strong> <?php echo htmlspecialchars($row['site_domain']); ?></p>   


                            <p><strong>Publisher`s Rate (Text Ads):</strong> <?php echo htmlspecialchars($row['rate_text_ads']); ?></p>
                            <p><strong>Budget`s Advertiser Per Click:</strong> <?php echo htmlspecialchars($row['budget_per_click_textads']); ?></p>
                            <p><strong>Provider AdNetwork:</strong> <?php echo htmlspecialchars($row['source_advertisement_domain']); ?></p>

                            <p><strong>Published:</strong> <?php echo $row['is_published'] ? 'Yes' : 'No'; ?></p>
                            <p><strong>Paused:</strong> <?php echo $row['is_paused'] ? 'Yes' : 'No'; ?></p>
                            <p><strong>Expired:</strong> <?php echo $row['is_expired'] ? 'Yes' : 'No'; ?></p>
                            <p><strong>Approved by Publisher:</strong> <?php echo $row['is_approved_by_publisher'] ? 'Yes' : 'No'; ?>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal" 
                                        data-id="<?php echo $row['id_mapping']; ?>" 
                                        data-is_approved_by_publisher="<?php echo $row['is_approved_by_publisher']; ?>">
                                    Edit
                                </button>
                            </p>
                            <p><strong>Approved by Advertiser:</strong> <?php echo $row['is_approved_by_advertiser'] ? 'Yes' : 'No'; ?></p>
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
