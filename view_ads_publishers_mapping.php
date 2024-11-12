<?php
// view_ads_publishers_mapping.php
include("db.php");
include("function.php");
session_start();

// Database connection
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


// Check if a success message is set in the query string
if (isset($_GET['status']) && $_GET['status'] == 'success') {
    echo '<div class="alert alert-success" role="alert">
            Approval/Rejection Status has been successfully updated!
          </div>';
}



// Get user ID and provider domain URL
$this_providers_id = 1;
$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);


// Pagination setup
$limit = 500; // Results per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Get current page number
$offset = ($page - 1) * $limit; // Offset for SQL query

// Sorting setup
$sort_by = isset($_GET['sort']) && in_array($_GET['sort'], ['rate_text_ads', 'budget_per_click_textads']) ? $_GET['sort'] : 'rate_text_ads';
$sort_order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

// Get local_ads_id from the query string (mandatory)
if (!isset($_GET['local_ads_id']) || empty($_GET['local_ads_id'])) {
    exit("Access denied: 'local_ads_id' is required.");
}

$local_ads_id = (int)$_GET['local_ads_id'];


// Get local_ads_id from the query string (if provided)
$local_ads_id = isset($_GET['local_ads_id']) ? (int)$_GET['local_ads_id'] : null;

// Prepare SQL query for main ads data
$sql = "SELECT * FROM mapping_advertisers_ads_publishers_site 
        WHERE owner_advertisers_id = ? AND ads_providers_domain_url = ?";

// Add filter for `local_ads_id` if provided
if ($local_ads_id) {
    $sql .= " AND local_ads_id = ?";
}

// Add sorting and pagination
$sql .= " ORDER BY $sort_by $sort_order LIMIT ? OFFSET ?";

// Prepare the statement for main ads data
if ($local_ads_id) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isiii', $user_id, $this_providers_domain_url, $local_ads_id, $limit, $offset);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isii', $user_id, $this_providers_domain_url, $limit, $offset);
}

// Execute the query for main ads data
$stmt->execute();
$result = $stmt->get_result();

// Fetch the advertisers_ads data
$ads_sql = "SELECT local_ads_id, title_ads, description_ads, landingpage_ads, image_url , 
budget_allocation, budget_per_click_textads, current_spending , current_spending_from_partner, current_click, current_click_partner, ispublished    , published_date , last_updated_spending  , is_expired , expired_date 
FROM advertisers_ads WHERE local_ads_id = ?";
$ads_stmt = $conn->prepare($ads_sql);
$ads_stmt->bind_param('i', $local_ads_id);
$ads_stmt->execute();
$ads_stmt->bind_result($local_ads_id, $title_ads, $description_ads, $landingpage_ads, $image_url , 
    $budget_allocation, $budget_per_click_textads, $current_spending , $current_spending_from_partner, $current_click, $current_click_partner, $ispublished    , $published_date , $last_updated_spending  , $is_expired , $expired_date );
$ads_stmt->fetch();
$ads_stmt->close();

$total_spending_local_and_partner =$current_spending + $current_spending_from_partner;
$remaining_buget = $budget_allocation - $total_spending_local_and_partner;

// Count total records for pagination
$count_sql = "SELECT COUNT(*) FROM mapping_advertisers_ads_publishers_site 
              WHERE owner_advertisers_id = ? AND ads_providers_domain_url = ?";
if ($local_ads_id) {
    $count_sql .= " AND local_ads_id = ?";
}

$count_stmt = $conn->prepare($count_sql);

if ($local_ads_id) {
    $count_stmt->bind_param('isi', $user_id, $this_providers_domain_url, $local_ads_id);
} else {
    $count_stmt->bind_param('is', $user_id, $this_providers_domain_url);
}

$count_stmt->execute();
$count_stmt->bind_result($total_records);
$count_stmt->fetch();
$total_pages = ceil($total_records / $limit); // Calculate total pages
$count_stmt->close();

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertisers Ads Publishers Mapping</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <?php include("main_menu.php") ?>
        <?php include("include_advertiser_menu.php") ?>

        <h1 class="text-center mb-4">Advertisers Ads Publishers Mapping</h1>

  <!-- Display Advertisers Ads Info -->
        <div class="mb-4">
            <h3>Ads Information</h3>
            <p><strong>Local Ads ID:</strong> <?php echo htmlspecialchars($local_ads_id); ?></p>
           
            

        <p><strong>Title:</strong> <?php echo htmlspecialchars($title_ads); ?></p>
           
            <p><strong>Description:</strong> <?php echo htmlspecialchars($description_ads); ?></p>
            <p><strong>Landing Page:</strong> <a href="<?php echo htmlspecialchars($landingpage_ads); ?>" target="_blank"><?php echo htmlspecialchars($landingpage_ads); ?></a></p>
            <p><strong>Image URL:</strong> <img src="<?php echo htmlspecialchars($image_url); ?>" alt="Ad Image" style="max-width: 100px;"></p>
            
            <p><strong>budget_per_click_textads: Rp </strong> <?php echo $budget_per_click_textads; ?></p>
                

                <p><strong>Budget Allocation: Rp </strong> <?php echo number_format($budget_allocation); ?></p>

                
<?php
// 
$url_report_local = $this_providers_domain_url."/clicks_ads_local_detail.php?local_ads_id=".$local_ads_id."&click_time=&ads_providers_domain_url=".$this_providers_domain_url;

$url_report_partner = $this_providers_domain_url."/clicks_ads_partner_detail.php?local_ads_id=".$local_ads_id."&click_time=&ads_providers_domain_url=".$this_providers_domain_url;


?>
                <p><strong>Current Spending Local: Rp </strong>
                <a  href="<?php echo $url_report_local  ?>"> <?php echo number_format($current_spending); ?></a></p>
                <p><strong>Current Spending from partner: Rp </strong> <a  href="<?php echo $url_report_partner  ?>"><?php echo number_format($current_spending_from_partner); ?></a></p>


               
        <p><strong>Total Current Spending: Rp </strong>
                <?php echo number_format($total_spending_local_and_partner); ?></p>
                



                 <p><strong>remaining_buget: Rp </strong> <?php echo number_format($remaining_buget); ?></p>

                <p><strong>last_updated_spending: </strong> <?php echo $last_updated_spending; ?></p>
       

<p><strong>ispublished:  </strong> <?php echo $ispublished ? 'Yes' : 'No' ; ?></p>
<p><strong>published_date:  </strong> <?php echo $published_date; ?></p>



        </div>


        <!-- Sorting Options -->
        <div class="text-end mb-3">
            <a href="view_ads_publishers_mapping.php?sort=rate_text_ads&order=<?php echo $sort_order === 'ASC' ? 'desc' : 'asc'; ?><?php echo $local_ads_id ? '&local_ads_id=' . $local_ads_id : ''; ?>" class="btn btn-secondary">
                Sort by Rate (<?php echo $sort_order === 'ASC' ? 'Desc' : 'Asc'; ?>)
            </a>
            <a href="view_ads_publishers_mapping.php?sort=budget_per_click_textads&order=<?php echo $sort_order === 'ASC' ? 'desc' : 'asc'; ?><?php echo $local_ads_id ? '&local_ads_id=' . $local_ads_id : ''; ?>" class="btn btn-secondary">
                Sort by Budget per Click (<?php echo $sort_order === 'ASC' ? 'Desc' : 'Asc'; ?>)
            </a>
        </div>

        
        
        <!-- Display Data in Cards -->
        <div class="row">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <p><strong>Site Name:</strong> 
                                    <h5 class="card-title"><?php echo htmlspecialchars($row['site_name']); ?></h5></p>

                                <p><strong>Publishers site local id:</strong> <?php echo htmlspecialchars($row['publishers_site_local_id']); ?></p>

                                  <p><strong>Pubs providers domain:</strong> <?php echo htmlspecialchars($row['pubs_providers_domain_url']); ?></p>



                                <p><strong>Domain:</strong> <a href="<?php echo htmlspecialchars($row['site_domain']); ?>" target="_blank"><?php echo htmlspecialchars($row['site_domain']); ?></a></p>
                                <p><strong>Publisher Rate per Click:</strong> <?php echo htmlspecialchars($row['rate_text_ads']); ?></p>
                                <p><strong>Advertiser's Budget per Click:</strong> <?php echo htmlspecialchars($row['budget_per_click_textads']); ?></p>

                                <!-- New Fields -->
                                <p><strong>Published:</strong> <?php echo $row['is_published'] ? 'Yes' : 'No'; ?></p>
                                <p><strong>Paused:</strong> <?php echo $row['is_paused'] ? 'Yes' : 'No'; ?></p>
                                <p><strong>Expired:</strong> <?php echo $row['is_expired'] ? 'Yes' : 'No'; ?></p>
                                <p><strong>Publisher Approval:</strong> <?php echo $row['is_approved_by_publisher'] ? 'Yes' : 'No'; ?></p>
                                

                                <p><strong>Advertiser Approval:</strong> <?php echo $row['is_approved_by_advertiser'] ? 'Yes' : 'No'; ?></p>


                <!-- Advertiser Approve/Reject Buttons -->
                        <div class="d-flex justify-content-between">
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#editApprovalModal<?php echo $row['id']; ?>">Edit Approval</button>
                        </div>



            <!-- Modal for Editing Approval -->
            <div class="modal fade" id="editApprovalModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editApprovalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editApprovalLabel">Edit Advertiser Approval</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="update_approval_advertiser.php" method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="local_ads_id" value="<?php echo $local_ads_id; ?>">
                                 <input type="hidden" name="publishers_site_local_id" value="<?php echo $row['publishers_site_local_id']; ?>">



                                <div class="mb-3">
                                    <label for="is_approved_by_advertiser" class="form-label">Advertiser Approval</label>
                                    <select class="form-select" name="is_approved_by_advertiser" id="is_approved_by_advertiser">
                                        <option value="1" <?php echo $row['is_approved_by_advertiser'] ? 'selected' : ''; ?>>Approved</option>
                                        <option value="0" <?php echo !$row['is_approved_by_advertiser'] ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>






                                <p><strong>Published Date:</strong> <?php echo htmlspecialchars($row['published_date']); ?></p>
                                <p><strong>Paused Date:</strong> <?php echo htmlspecialchars($row['paused_date']); ?></p>
                                <p><strong>Expired Date:</strong> <?php echo htmlspecialchars($row['expired_date']); ?></p>
                                <p><strong>Approval Date (Publisher):</strong> <?php echo htmlspecialchars($row['approval_date_publisher']); ?></p>
                                <p><strong>Approval Date (Advertiser):</strong> <?php echo htmlspecialchars($row['approval_date_advertiser']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <p class="text-center">No records found</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="view_ads_publishers_mapping.php?page=<?php echo $i; ?>&sort=<?php echo htmlspecialchars($sort_by); ?>&order=<?php echo htmlspecialchars($sort_order); ?><?php echo $local_ads_id ? '&local_ads_id=' . $local_ads_id : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>

    <!-- Bootstrap JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
