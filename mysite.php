<?php

// mysite.php 

// Include database connection
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

// Create a connection to the MySQL database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check the connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

try {
    $pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    exit("Database connection failed.");
}


// Get user ID and provider domain URL
$this_providers_id = 1;
//$this_providers_domain_url = get_providers_domain_url($mysqli, $this_providers_id);


$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);


//$this_providers_name =  get_providers_name($mysqli, $this_providers_id);


$this_providers_name = getProvidersNameById_JSON("providers_data.json", 1);


// Handle form submission for delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = intval($_POST['id']);
    $providers_domain_url = $_POST['providers_domain_url'];


    // Check if the data exists in mapping_advertisers_ads_publishers_site

    $sql_delete="SELECT COUNT(*) AS count FROM mapping_advertisers_ads_publishers_site 
                                    WHERE publishers_local_id = ? AND pubs_providers_domain_url = ?";
    $stmt_check = $mysqli->prepare($sql_delete);
  //  echo "<br>sql_delete = ".$sql_delete;
  //  echo "<br>id = ".$id;
    
   // echo "<br>providers_domain_url = ".$providers_domain_url;
    

    $stmt_check->bind_param("is", $id, $providers_domain_url);
    $stmt_check->execute();
    $stmt_check_result = $stmt_check->get_result();
    $count = $stmt_check_result->fetch_assoc()['count'];
    $stmt_check->close();

    //echo "<br>count = ".$count;
    //exit;

    // If data exists in mapping_advertisers_ads_publishers_site, prevent deletion
    if ($count > 0) {
        // Show alert for existing data in mapping_advertisers_ads_publishers_site
    echo '
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Warning!</strong> Data exists in <em>mapping_advertisers_ads_publishers_site</em>. You cannot delete this site.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
    } else {
        // If data doesn't exist, delete from publishers_site
        $stmt_delete = $mysqli->prepare("DELETE FROM publishers_site WHERE id = ? AND providers_domain_url = ? AND publishers_local_id = ?");
        $stmt_delete->bind_param("isi", $id, $providers_domain_url, $user_id);
        if ($stmt_delete->execute()) {
           echo '
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Success!</strong> Site deleted successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
        } else {
            echo '
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> Error deleting site: ' . $stmt_delete->error . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
        }
        $stmt_delete->close();
    }
}


// Handle form submission for updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $site_name = $_POST['site_name'];
    $site_domain = $_POST['site_domain'];
    $rate_text_ads = intval($_POST['rate_text_ads']);
    $site_desc = $_POST['site_desc'];
    $providers_domain_url = $_POST['providers_domain_url'];
     $advertiser_allowed = $_POST['advertiser_allowed'];
    $advertiser_rejected = $_POST['advertiser_rejected'];
    $alternate_code = $_POST['alternate_code']; // New field
    $id = intval($_POST['id']);
    
   // Update query to include advertiser_allowed and advertiser_rejected
       // Set timezone to GMT+7
        date_default_timezone_set('Asia/Jakarta'); // GMT +7

 // Update query to include alternate_code
    $stmt_update = $mysqli->prepare("UPDATE publishers_site 
                                     SET site_name = ?, site_domain = ?, rate_text_ads = ?, site_desc = ?, advertiser_allowed = ?, advertiser_rejected = ?, alternate_code = ?,
                                     last_updated = now()
                                     WHERE id = ? AND providers_domain_url = ?");
    $stmt_update->bind_param("ssissssis", $site_name, $site_domain, $rate_text_ads, $site_desc, $advertiser_allowed, $advertiser_rejected, $alternate_code, $id, $providers_domain_url);


    $stmt_update->execute();
    $stmt_update->close();


    // Now update the rate_text_ads in mapping_advertisers_ads_publishers_site for matching publishers_site_local_id
    $sql_update = "UPDATE mapping_advertisers_ads_publishers_site
        SET rate_text_ads = ?
         WHERE publishers_site_local_id = ?
        AND pubs_providers_domain_url = ?";
    $stmt_mapping_update = $mysqli->prepare($sql_update);

        //echo "<br>sql_update: ".$sql_update;
        //echo "<br>rate_text_ads: ".$rate_text_ads;
        //echo "<br>this_providers_domain_url: ".$this_providers_domain_url;
       // echo "<br>id: ".$id;
        
    $stmt_mapping_update->bind_param("iis", $rate_text_ads, $id,$this_providers_domain_url);
    $stmt_mapping_update->execute();
    $stmt_mapping_update->close();

}

// Set the number of records per page
$records_per_page = 20;

// Get the current page number from the URL, if none is set default to 1
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;

// Calculate the offset for the query
$offset = ($page - 1) * $records_per_page;

// Prepare the SQL query to select data securely, including site_desc, filtered by publishers_local_id
$stmt = $mysqli->prepare("SELECT id, providers_name, providers_domain_url, site_name, site_domain, site_desc, rate_text_ads, isbanned , advertiser_allowed , advertiser_rejected , alternate_code , internal_blog
                          FROM publishers_site 
                          WHERE publishers_local_id = ?
                           ORDER BY last_updated desc
                          LIMIT ?, ?");
$stmt->bind_param("iii", $user_id, $offset, $records_per_page);

// Execute the query
$stmt->execute();

// Get the result set
$result = $stmt->get_result();

// Fetch all results
$publishers = $result->fetch_all(MYSQLI_ASSOC);

// Close the statement
$stmt->close();

// Get the total number of records for pagination, filtered by publishers_local_id
$total_stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM publishers_site WHERE publishers_local_id = ?");
$total_stmt->bind_param("i", $user_id);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_rows = $total_result->fetch_assoc()['total'];
$total_stmt->close();

// Calculate the total number of pages
$total_pages = ceil($total_rows / $records_per_page);

// Close the database connection
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publishers Site Data</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        .pagination {
            text-align: center;
            margin: 20px 0;
        }
        .pagination a {
            color: #007bff;
            padding: 8px 16px;
            text-decoration: none;
            border: 1px solid #ddd;
            margin: 0 5px;
        }
        .pagination a.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .pagination a:hover {
            background-color: #ddd;
        }
    </style>
</head>
<body>

<div class="container">

    <?php include("main_menu.php"); ?>
    <?php include("include_publisher_menu.php"); ?>

    <h1>Publishers Site Data</h1>
    
     <div class="row">
        <?php if (!empty($publishers)): ?>
            <?php foreach ($publishers as $publisher): 

            $rate_text_ads = $publisher['rate_text_ads'];
            $rate_text_ads_margin_local  = $rate_text_ads + ($rate_text_ads/2);
            $rate_text_ads_margin_partner  = $rate_text_ads * 2;


            $pub_id = $publisher['id'];
            $pubs_providers_domain_url= $publisher['providers_domain_url'];
            $revenuePublishers = calculateTotalRevenuePublishers($pub_id, $pubs_providers_domain_url, $pdo);

            $link_report="clicks_publisher_detail.php?pub_id=".$pub_id."&pubs_providers_domain_url=".$pubs_providers_domain_url;

                ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">Publisher ID: <?php echo htmlspecialchars($publisher['id']); ?></h5>
                            <p><strong>Provider Name:</strong> <?php echo htmlspecialchars($publisher['providers_name']); ?></p>
                            <p><strong>Provider Domain URL:</strong> <?php echo htmlspecialchars($publisher['providers_domain_url']); ?></p>
                            <p><strong>Site Name:</strong> <?php echo htmlspecialchars($publisher['site_name']); ?></p>
                            <p><strong>Site Domain:</strong> <?php echo htmlspecialchars($publisher['site_domain']); ?></p>
                            

                            <p><strong>Site Description:</strong> <?php echo htmlspecialchars($publisher['site_desc']); ?></p>
                            
                             <p><strong>advertiser_allowed:</strong> <?php echo htmlspecialchars($publisher['advertiser_allowed']); ?></p>

                              <p><strong>advertiser_rejected</strong> <?php echo htmlspecialchars($publisher['advertiser_rejected']); ?></p>

                            <p><strong>Rate Publisher (Text Ads):Rp </strong> <?php echo htmlspecialchars($publisher['rate_text_ads']); ?></p>

                            <p><strong>Selling Rate for Local Advertiser(Text Ads):Rp </strong> <?php echo number_format($rate_text_ads_margin_local); ?></p>
                            <p><strong>Selling Rate for Partner Global Advertiser(Text Ads):Rp </strong> <?php echo number_format($rate_text_ads_margin_partner); ?></p>


                            <p><strong>Revenue Publishers (Text Ads):Rp <a href="<?php echo  $link_report?>"></strong> <?php echo number_format($revenuePublishers); ?></a></p>



                            <p><strong>Banned:</strong> <?php echo $publisher['isbanned'] ? 'Yes' : 'No'; ?></p>
                            
                          
                          <!-- Edit Button -->
                          <button class="btn btn-warning mb-2" data-bs-toggle="modal" data-bs-target="#editModal"
    data-id="<?php echo $publisher['id']; ?>"
    data-site_name="<?php echo htmlspecialchars($publisher['site_name']); ?>"
    data-site_domain="<?php echo htmlspecialchars($publisher['site_domain']); ?>"
    data-site_desc="<?php echo htmlspecialchars($publisher['site_desc']); ?>"
    data-rate_text_ads="<?php echo htmlspecialchars($publisher['rate_text_ads']); ?>"
    data-advertiser_allowed="<?php echo htmlspecialchars($publisher['advertiser_allowed']); ?>"
    data-advertiser_rejected="<?php echo htmlspecialchars($publisher['advertiser_rejected']); ?>"
    data-alternate_code="<?php echo htmlspecialchars($publisher['alternate_code']); ?>"
    data-providers_domain_url="<?php echo htmlspecialchars($publisher['providers_domain_url']); ?>">
    Edit
</button>

               <button class="btn btn-warning mb-2" data-bs-toggle="modal" data-bs-target="#takeScriptModal-<?php echo $publisher['id']; ?>"
    data-id="<?php echo $publisher['id']; ?>">
    Take Script 
</button>


<!-- Take Script Modal for each publisher -->
<div class="modal fade" id="takeScriptModal-<?php echo $publisher['id']; ?>" tabindex="-1" aria-labelledby="takeScriptLabel-<?php echo $publisher['id']; ?>" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="takeScriptLabel-<?php echo $publisher['id']; ?>">Take Publisher Script</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Copy the script below to include in your website:</p>Parameter `column` dan `maxads` bisa dimodifikasi, sesuai keperluan. Sebagai contoh `column` bisa diset menjadi 1 atau 3, `maxads` bisa diset menjadi 2,4,5 dan sebagainya.
        <p><b>Script cocok untuk Sidebar:</b>
        <textarea id="script1-<?php echo $publisher['id']; ?>" class="form-control" rows="5" readonly>
<script type='text/javascript' src='<?php echo $this_providers_domain_url; ?>/show_ads_native.js.php?pubId=<?php echo $publisher['id']; ?>&pubProvName=<?php echo $this_providers_name; ?>&maxads=10&column=1'></script>
        </textarea>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="copyToClipboard('script1-<?php echo $publisher['id']; ?>')">Copy Script</button>
      </div>

      <div class="modal-body">
        Parameter `column` dan `maxads` bisa dimodifikasi, sesuai keperluan. Sebagai contoh `column` bisa diset menjadi 1 atau 3, `maxads` bisa diset menjadi 2,4,5 dan sebagainya.
        <p><b>Script cocok untuk header, content, footer:</b>
        <textarea id="script2-<?php echo $publisher['id']; ?>" class="form-control" rows="5" readonly>
<script type='text/javascript' src='<?php echo $this_providers_domain_url; ?>/show_ads_native_landscape.js.php?pubId=<?php echo $publisher['id']; ?>&pubProvName=<?php echo $this_providers_name; ?>&maxads=10&column=1'></script>
        </textarea>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="copyToClipboard('script2-<?php echo $publisher['id']; ?>')">Copy Script</button>
      </div>
    </div>
  </div>
</div>



                            <!-- View Button -->
                            <a href="mysite_ads.php?publisher_site_local_id=<?php echo $publisher['id']; ?>" class="btn btn-info mb-2">
                                View Advertisement in this site
                            </a>

                            <!-- Delete Button -->

<?php
 if ($publisher['internal_blog']==0 ) {
?>
<button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal"
    data-id="<?php echo $publisher['id']; ?>"
    data-providers_domain_url="<?php echo htmlspecialchars($publisher['providers_domain_url']); ?>">
    Delete
</button>
<?php

 }
 
?>


                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-warning text-center">No records found.</div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>">&laquo; Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="<?php echo ($page == $i) ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    var copyText = document.getElementById(elementId);
    copyText.select();
    document.execCommand("copy");
    alert("Script copied to clipboard for " + elementId);
}
</script>


<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Delete Publisher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this publisher site?</p>
                <input type="hidden" id="delete_id" name="id">
                <input type="hidden" id="delete_providers_domain_url" name="providers_domain_url">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger" name="delete">Delete</button>
            </div>
        </div>
    </form>
  </div>
</div>


<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Publisher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="site_name" class="form-label">Site Name</label>
                    <input type="text" class="form-control" id="site_name" name="site_name" required>
                </div>
                <div class="mb-3">
                    <label for="site_domain" class="form-label">Site Domain</label>
                    <input type="text" class="form-control" id="site_domain" name="site_domain" required>
                </div>
                <div class="mb-3">
                    <label for="site_desc" class="form-label">Site Description</label>
                    <textarea class="form-control" id="site_desc" name="site_desc" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="rate_text_ads" class="form-label">Rate (Text Ads)</label>
                    <input type="number" class="form-control" id="rate_text_ads" name="rate_text_ads" required>
                </div>

<div class="mb-3">
    <label for="advertiser_allowed" class="form-label">Advertiser Allowed</label>
    <textarea class="form-control" id="advertiser_allowed" name="advertiser_allowed" rows="3" required></textarea>
    <small class="form-text text-muted">Enter the types of advertisements that are allowed.</small>
</div>
<div class="mb-3">
    <label for="advertiser_rejected" class="form-label">Advertiser Rejected</label>
    <textarea class="form-control" id="advertiser_rejected" name="advertiser_rejected" rows="3" required></textarea>
    <small class="form-text text-muted">Enter the types of advertisements that are not allowed (e.g., MLM, political ads, gambling ads).</small>
</div>


                <!-- New alternate_code field -->
                <div class="mb-3">
                    <label for="alternate_code" class="form-label">Alternate Code</label>
                    <textarea class="form-control" id="alternate_code" name="alternate_code" rows="3"></textarea>
                    <small class="form-text text-muted">Enter the alternate code to show when no ads are available.</small>
                </div>



                <input type="hidden" id="providers_domain_url" name="providers_domain_url">
                <input type="hidden" id="id" name="id">
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" name="update">Save changes</button>
            </div>
        </div>
    </form>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var editModal = document.getElementById('editModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var siteName = button.getAttribute('data-site_name');
        var siteDomain = button.getAttribute('data-site_domain');
        var siteDesc = button.getAttribute('data-site_desc');
        var rateTextAds = button.getAttribute('data-rate_text_ads');
        var providersDomainUrl = button.getAttribute('data-providers_domain_url');
       var alternateCode = button.getAttribute('data-alternate_code');


        var modalId = editModal.querySelector('#id');
        var modalSiteName = editModal.querySelector('#site_name');
        var modalSiteDomain = editModal.querySelector('#site_domain');
        var modalSiteDesc = editModal.querySelector('#site_desc');
        var modalRateTextAds = editModal.querySelector('#rate_text_ads');
        var modalProvidersDomainUrl = editModal.querySelector('#providers_domain_url');

        var advertiserAllowed = button.getAttribute('data-advertiser_allowed');
        var advertiserRejected = button.getAttribute('data-advertiser_rejected');

        var modalAdvertiserAllowed = editModal.querySelector('#advertiser_allowed');
        var modalAdvertiserRejected = editModal.querySelector('#advertiser_rejected');
         var modalAlternateCode = editModal.querySelector('#alternate_code');

        modalAdvertiserAllowed.value = advertiserAllowed;
        modalAdvertiserRejected.value = advertiserRejected;


        modalId.value = id;
        modalSiteName.value = siteName;
        modalSiteDomain.value = siteDomain;
        modalSiteDesc.value = siteDesc;
        modalRateTextAds.value = rateTextAds;
        modalProvidersDomainUrl.value = providersDomainUrl;
         modalAlternateCode.value = alternateCode;
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var providersDomainUrl = button.getAttribute('data-providers_domain_url');

        var modalDeleteId = deleteModal.querySelector('#delete_id');
        var modalDeleteProvidersDomainUrl = deleteModal.querySelector('#delete_providers_domain_url');

        modalDeleteId.value = id;
        modalDeleteProvidersDomainUrl.value = providersDomainUrl;
    });
});
</script>




</body>
</html>
