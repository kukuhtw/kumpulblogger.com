<?php
/*
admin/manage_ads.php
*/
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    // If not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

// Include the database connection
include("../db.php");
include("function_admin.php");



$loginemail_admin = $_SESSION['loginemail_admin'];
// Set default page number and items per page
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search and filter criteria
$search_title = isset($_GET['search_title']) ? $_GET['search_title'] : '';
$is_paid = isset($_GET['is_paid']) ? $_GET['is_paid'] : '';
$ispublished = isset($_GET['ispublished']) ? $_GET['ispublished'] : '';
$is_paused = isset($_GET['is_paused']) ? $_GET['is_paused'] : '';
$is_expired = isset($_GET['is_expired']) ? $_GET['is_expired'] : '';

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

// Base SQL query
$sql = "SELECT COUNT(*) AS total FROM advertisers_ads WHERE 1=1";

// Add search and filter conditions
if (!empty($search_title)) {
    $sql .= " AND title_ads LIKE ?";
}
if ($is_paid !== '') {
    $sql .= " AND is_paid = ?";
}
if ($ispublished !== '') {
    $sql .= " AND ispublished = ?";
}
if ($is_paused !== '') {
    $sql .= " AND is_paused = ?";
}
if ($is_expired !== '') {
    $sql .= " AND is_expired = ?";
}

// Prepare and bind parameters
$stmt = $conn->prepare($sql);
$bind_types = '';
$params = [];

if (!empty($search_title)) {
    $bind_types .= 's';
    $params[] = '%' . $search_title . '%';
}
if ($is_paid !== '') {
    $bind_types .= 'i';
    $params[] = $is_paid;
}
if ($ispublished !== '') {
    $bind_types .= 'i';
    $params[] = $ispublished;
}
if ($is_paused !== '') {
    $bind_types .= 'i';
    $params[] = $is_paused;
}
if ($is_expired !== '') {
    $bind_types .= 'i';
    $params[] = $is_expired;
}

// Bind parameters
if (!empty($params)) {
    $stmt->bind_param($bind_types, ...$params);
}

// Execute and get the total ads count
$stmt->execute();
$result = $stmt->get_result();
$total_ads = $result->fetch_assoc()['total'];

// Fetch ads for the current page
$sql = "SELECT * FROM advertisers_ads WHERE 1=1 ";

// Add search and filter conditions
if (!empty($search_title)) {
    $sql .= " AND title_ads LIKE ?";
}
if ($is_paid !== '') {
    $sql .= " AND is_paid = ?";
}
if ($ispublished !== '') {
    $sql .= " AND ispublished = ?";
}
if ($is_paused !== '') {
    $sql .= " AND is_paused = ?";
}
if ($is_expired !== '') {
    $sql .= " AND is_expired = ?";
}

$sql .= " ORDER by id desc";

// Append pagination
$sql .= " LIMIT ? OFFSET ?";

// Prepare the final statement
$stmt = $conn->prepare($sql);
$bind_types .= 'ii'; // For limit and offset
$params[] = $limit;
$params[] = $offset;
$stmt->bind_param($bind_types, ...$params);

// Execute the query
$stmt->execute();
$result = $stmt->get_result();

// Calculate total pages
$total_pages = ceil($total_ads / $limit);

?>
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ads List</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <?php include("style_toogle.php") ?>
    <style>
        /* Your CSS styles */
    </style>
</head>

<body>

<div class="navbar">
    Admin Dashboard
    <a href="logout.php" style="float:right;">Logout</a>
</div>

<?php include("sidebar_menu.php"); ?>

<div class="container" id="mainContent"> 
    
    <div class="content">
        <h2>Advertisers Ads List</h2>

        <!-- Search and Filter Form -->
        <form method="GET" action="manage_ads.php" class="form-inline mb-4">
            <div class="form-group mr-2">
                <label for="search_title" class="mr-2">Title</label>
                <input type="text" name="search_title" id="search_title" class="form-control" value="<?php echo htmlspecialchars($search_title); ?>">
            </div>
            <div class="form-group mr-2">
                <label for="is_paid" class="mr-2">Paid</label>
                <select name="is_paid" id="is_paid" class="form-control">
                    <option value="">All</option>
                    <option value="1" <?php if ($is_paid === '1') echo 'selected'; ?>>Yes</option>
                    <option value="0" <?php if ($is_paid === '0') echo 'selected'; ?>>No</option>
                </select>
            </div>
            <div class="form-group mr-2">
                <label for="ispublished" class="mr-2">Published</label>
                <select name="ispublished" id="ispublished" class="form-control">
                    <option value="">All</option>
                    <option value="1" <?php if ($ispublished === '1') echo 'selected'; ?>>Yes</option>
                    <option value="0" <?php if ($ispublished === '0') echo 'selected'; ?>>No</option>
                </select>
            </div>
            <div class="form-group mr-2">
                <label for="is_paused" class="mr-2">Paused</label>
                <select name="is_paused" id="is_paused" class="form-control">
                    <option value="">All</option>
                    <option value="1" <?php if ($is_paused === '1') echo 'selected'; ?>>Yes</option>
                    <option value="0" <?php if ($is_paused === '0') echo 'selected'; ?>>No</option>
                </select>
            </div>
            <div class="form-group mr-2">
                <label for="is_expired" class="mr-2">Expired</label>
                <select name="is_expired" id="is_expired" class="form-control">
                    <option value="">All</option>
                    <option value="1" <?php if ($is_expired === '1') echo 'selected'; ?>>Yes</option>
                    <option value="0" <?php if ($is_expired === '0') echo 'selected'; ?>>No</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <div class="card">
            <div class="card-header">
                Ads Data
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Image</th>
                            <th>Budget</th>
                            <th>Clicks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                            <?php 
                                        
                $advertisers_id = $row['advertisers_id']; 
//echo "<br>advertisers_id: ~".$advertisers_id."~<br>";
    $updateLocalSpending = updateLocalSpending($conn, $advertisers_id);

//echo "<br>updateLocalSpending: ~".$updateLocalSpending."~<br>";

    $updateGlobalSpending = updateGlobalSpending($conn, $advertisers_id);

$id=$row['id'] ;
$providers_domain_url  = $row['providers_domain_url'];
    updateCurrentClick_local($conn, $id, $providers_domain_url ); 

                echo "<br>ID: ".htmlspecialchars($row['id']); 


                            ?></td>
                            <td><?php echo htmlspecialchars($row['title_ads']); 
        echo "<br>".htmlspecialchars($row['providers_name']);
        echo "<br>".htmlspecialchars($row['landingpage_ads']); 
        
        $current_click = $row['current_click'];
        $current_click_partner = $row['current_click_partner'];
        $total_click = $row['total_click'];
        $total_click = $current_click + $current_click_partner;

         $budget_allocation = $row['budget_allocation'];
         $current_spending= $row['current_spending'];
         $current_spending_from_partner= $row['current_spending_from_partner'];
         $remaining_buget = $budget_allocation - ($current_spending +$current_spending_from_partner );


        ?>
            
        </td>
        <td><img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Ad Image" style="max-width: 100px;"></td> <!-- Display the image -->
<td>

    Budget Allocation: Rp <?php echo number_format($row['budget_allocation'],2); ?>
    <br>Remaining Budget: Rp <?php echo number_format($remaining_buget); ?>

</td>
                            <td>
            <?php echo htmlspecialchars($total_click); ?></td>
                            <td>
                     <a href="#" class="btn btn-info" data-toggle="modal" data-target="#detailsModal" 
   data-id="<?php echo $row['id']; ?>"
    data-title="<?php echo $row['title_ads']; ?>"
    data-advertiser_id="<?php echo $row['advertisers_id']; ?>"
data-landingpage="<?php echo $row['landingpage_ads']; ?>"
data-budget="<?php echo $row['budget_per_click_textads']; ?>"
    data-allocation="<?php echo $row['budget_allocation']; ?>"
                                   data-spending="<?php echo $row['current_spending']; ?>"
                                   data-spending-partner="<?php echo $row['current_spending_from_partner']; ?>"
                                   data-updated-spending="<?php echo $row['last_updated_spending']; ?>"
                                   data-published="<?php echo $row['ispublished']; ?>"
                                   data-publish-date="<?php echo $row['published_date']; ?>"
                                   data-paid="<?php echo $row['is_paid']; ?>"
                                   data-paid-date="<?php echo $row['paid_date']; ?>"
                                   data-paid-desc="<?php echo $row['paid_desc']; ?>"
                                   data-total-click="<?php echo $row['total_click']; ?>"
                                   data-current-click="<?php echo $row['current_click']; ?>"
                                   data-current-click-partner="<?php echo $row['current_click_partner']; ?>"
                                   data-expired="<?php echo $row['is_expired']; ?>"
                                   data-expired-date="<?php echo $row['expired_date']; ?>"
                                   data-paused="<?php echo $row['is_paused']; ?>"
                                   data-paused-date="<?php echo $row['paused_date']; ?>">
                                    View More
                                </a>
                                 <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#editModal" 
       data-id="<?php echo $row['id']; ?>"
       data-ispublished="<?php echo $row['ispublished']; ?>"
       data-publish-date="<?php echo $row['published_date']; ?>"
     data-is_paid="<?php echo $row['is_paid']; ?>"
   data-paid_date="<?php echo $row['paid_date']; ?>">

        Edit
    </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Pagination Links -->
                <nav>
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search_title=<?php echo urlencode($search_title); ?>&is_paid=<?php echo urlencode($is_paid); ?>&ispublished=<?php echo urlencode($ispublished); ?>&is_paused=<?php echo urlencode($is_paused); ?>&is_expired=<?php echo urlencode($is_expired); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search_title=<?php echo urlencode($search_title); ?>&is_paid=<?php echo urlencode($is_paid); ?>&ispublished=<?php echo urlencode($ispublished); ?>&is_paused=<?php echo urlencode($is_paused); ?>&is_expired=<?php echo urlencode($is_expired); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>


<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editModalLabel">Edit Publish Status</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="editForm" method="POST" action="update_publish_status.php">
          <input type="hidden" name="ad_id" id="editAdId">
          

          <div class="form-group">
    <label for="is_paid">Paid Status</label>
    <select name="is_paid" id="editIsPaid" class="form-control">
        <option value="1">Yes</option>
        <option value="0">No</option>
    </select>
</div>

<div class="form-group">
    <label for="paid_date">Paid Date</label>
    <input type="datetime-local" name="paid_date" id="editPaidDate" class="form-control">
</div>



          <div class="form-group">
            <label for="ispublished">Published Status</label>
            <select name="ispublished" id="editIspublished" class="form-control">
              <option value="1">Yes</option>
              <option value="0">No</option>
            </select>
          </div>

          <div class="form-group">
            <label for="publish_date">Publish Date</label>
            <input type="datetime-local" name="publish_date" id="editPublishDate" class="form-control">
          </div>

          <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
      </div>
    </div>
  </div>
</div>


<!-- Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-labelledby="detailsModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detailsModalLabel">Ads Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p><strong>Title Ads:</strong> <span id="title"></span></p> <!-- Add this line -->
    <p><strong>Advertiser ID:</strong> <span id="advertiser_id"></span></p> <!-- Add this line -->
    <p><strong>Landing Page:</strong> <span id="landingpage"></span></p> <!-- Add this line -->
  
        <p><strong>Budget per Click Text Ads:</strong> <span id="budget"></span></p>
        <p><strong>Budget Allocation:</strong> <span id="allocation"></span></p>
        <p><strong>Current Spending:</strong> <span id="spending"></span></p>
        <p><strong>Spending from Partner:</strong> <span id="spending-partner"></span></p>

        <!-- Display remaining budget -->
        <p><strong>Remaining Budget:</strong> <span id="remaining-budget"></span></p>


        <p><strong>Last Updated Spending:</strong> <span id="updated-spending"></span></p>
        <p><strong>Published:</strong> <span id="published"></span></p>
        <p><strong>Published Date:</strong> <span id="publish-date"></span></p>
        <p><strong>Paid:</strong> <span id="paid"></span></p>
        <p><strong>Paid Date:</strong> <span id="paid-date"></span></p>
        <p><strong>Paid Description:</strong> <span id="paid-desc"></span></p>
        <p><strong>Total Clicks:</strong> <span id="total-click"></span></p>
        <p><strong>Current Clicks:</strong> <span id="current-click"></span></p>
        <p><strong>Current Clicks from Partner:</strong> <span id="current-click-partner"></span></p>
        <p><strong>Expired:</strong> <span id="expired"></span></p>
        <p><strong>Expired Date:</strong> <span id="expired-date"></span></p>
        <p><strong>Paused:</strong> <span id="paused"></span></p>
        <p><strong>Paused Date:</strong> <span id="paused-date"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

<script>
$('#editModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget); // Button that triggered the modal
    var id = button.data('id');
    var ispublished = button.data('ispublished');
    var publishDate = button.data('publish-date');
    
    var isPaid = button.data('is_paid');
    var paidDate = button.data('paid_date');


    var modal = $(this);
    modal.find('#editAdId').val(id);
    modal.find('#editIspublished').val(ispublished);
    modal.find('#editPublishDate').val(publishDate);

    // Set is_paid and paid_date in the modal
    modal.find('#editIsPaid').val(isPaid);
    modal.find('#editPaidDate').val(paidDate);



});



   $('#detailsModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget); // Button that triggered the modal
    // Extract data from the button using data-* attributes
    var title = button.data('title');  // Add this line
    var landingpage = button.data('landingpage');  // Add this line
     var advertiser_id = button.data('advertiser_id');  // Add this line
   
    var budget = button.data('budget');
    var allocation = button.data('allocation');
    var spending = button.data('spending');
    var spendingPartner = button.data('spending-partner');
    var updatedSpending = button.data('updated-spending');


 // Calculate remaining budget
    var remainingBudget = allocation - (spending + spendingPartner);

   // Format the remaining budget using number_format
    var formattedRemainingBudget = remainingBudget.toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });


    var published = button.data('published');
    var publishDate = button.data('publish-date');
    var paid = button.data('paid');
    var paidDate = button.data('paid-date');
    var paidDesc = button.data('paid-desc');
    var totalClick = button.data('total-click');
    var currentClick = button.data('current-click');
    var currentClickPartner = button.data('current-click-partner');
    var expired = button.data('expired');
    var expiredDate = button.data('expired-date');
    var paused = button.data('paused');
    var pausedDate = button.data('paused-date');

    // Update the modal's content with the above variables
    var modal = $(this);
    modal.find('#title').text(title);  // Add this line
    modal.find('#landingpage').text(landingpage);  // Add this line
    modal.find('#advertiser_id').text(advertiser_id);  // Add this line
    modal.find('#budget').text(budget);
    modal.find('#allocation').text(allocation);
    modal.find('#spending').text(spending);
    modal.find('#spending-partner').text(spendingPartner);
    modal.find('#updated-spending').text(updatedSpending);
    
     // Set remaining budget
    //modal.find('#remaining-budget').text(remainingBudget);
     modal.find('#remaining-budget').text('Rp ' + formattedRemainingBudget);

    modal.find('#published').text(published ? 'Yes' : 'No');
    modal.find('#publish-date').text(publishDate);
    modal.find('#paid').text(paid ? 'Yes' : 'No');
    modal.find('#paid-date').text(paidDate);
    modal.find('#paid-desc').text(paidDesc);
    modal.find('#total-click').text(totalClick);
    modal.find('#current-click').text(currentClick);
    modal.find('#current-click-partner').text(currentClickPartner);
    modal.find('#expired').text(expired ? 'Yes' : 'No');
    modal.find('#expired-date').text(expiredDate);
    modal.find('#paused').text(paused ? 'Yes' : 'No');
    modal.find('#paused-date').text(pausedDate);
});

</script>

<?php
include("js_toogle.php");
//$mysqli->close();
$stmt->close();
$conn->close();


?>

</body>
</html>

