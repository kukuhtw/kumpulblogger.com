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
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
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
$filter_query = http_build_query([
    'search_title' => $search_title,
    'is_paid' => $is_paid,
    'ispublished' => $ispublished,
    'is_paused' => $is_paused,
    'is_expired' => $is_expired,
]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ads List</title>
    <?php include("style_toogle.php"); ?>
    <style>
        .ads-filter { display: grid; grid-template-columns: minmax(220px, 2fr) repeat(4, minmax(120px, 1fr)) auto; gap: .75rem; align-items: end; }
        .ads-filter .form-group { margin: 0; }
        .ad-thumbnail { width: 96px; height: 64px; border-radius: .4rem; object-fit: cover; background: #e9ecef; }
        .ad-title { min-width: 220px; max-width: 360px; }
        .ad-url { display: block; max-width: 320px; color: #6c757d; font-size: .78rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .action-buttons { display: flex; flex-wrap: wrap; gap: .4rem; min-width: 145px; }
        .actions-column { position: sticky; right: 0; z-index: 2; background: #fff !important; box-shadow: -5px 0 8px rgba(31,41,55,.06); }
        thead .actions-column { z-index: 3; background: #f8f9fa !important; }
        .ad-statuses { display: flex; flex-wrap: wrap; gap: .4rem; min-width: 190px; }
        .ad-statuses .badge { padding: .45rem .6rem; font-size: .75rem; font-weight: 600; }
        @media (max-width: 1199.98px) { .ads-filter { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 767.98px) { .ads-filter { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 767.98px) {
            .ads-table-responsive { overflow: visible; }
            .ads-table, .ads-table tbody, .ads-table tr, .ads-table td { display: block; width: 100%; }
            .ads-table thead { display: none; }
            .ads-table tbody { display: grid; gap: 1rem; }
            .ads-table tbody tr { overflow: hidden; border: 1px solid #e3e8ee; border-radius: .7rem; background: #fff; box-shadow: 0 2px 8px rgba(31,41,55,.06); }
            .ads-table tbody td { min-width: 0; max-width: none; padding: .75rem 1rem; border: 0; border-bottom: 1px solid #edf0f2; background: #fff !important; }
            .ads-table tbody td:last-child { border-bottom: 0; }
            .ads-table tbody td::before { content: attr(data-label); display: block; margin-bottom: .35rem; color: #6c757d; font-size: .7rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
            .ads-table .ad-title, .ads-table .ad-url { min-width: 0; max-width: 100%; }
            .ads-table .actions-column { position: static; box-shadow: none; }
            .ads-table .action-buttons { min-width: 0; }
            .ads-table .action-buttons .btn { flex: 1; }
            .ads-table .ad-statuses { min-width: 0; }
            .ad-thumbnail { width: 100%; height: 160px; }
        }
        @media (max-width: 575.98px) { .ads-filter { grid-template-columns: 1fr; } }
    </style>
</head>

<body>

<div class="admin-navbar">
    <a class="brand" href="dashboard_admin.php">Admin Dashboard</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
</div>

<?php include("sidebar_menu.php"); ?>

<main class="admin-main" id="mainContent"> 
    
    <div class="content">
        <div class="mb-4">
            <h1 class="page-title">Daftar Iklan Advertiser</h1>
            <p class="page-subtitle"><?php echo number_format($total_ads); ?> iklan ditemukan berdasarkan filter saat ini.</p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
        <?php endif; ?>

        <!-- Search and Filter Form -->
        <form method="GET" action="manage_ads.php" class="card card-body ads-filter mb-4">
            <div class="form-group">
                <label for="search_title">Judul</label>
                <input type="search" name="search_title" id="search_title" class="form-control" placeholder="Cari judul iklan" value="<?php echo htmlspecialchars($search_title); ?>">
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
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter mr-1"></i> Terapkan</button>
        </form>

        <div class="card">
            <div class="card-header">
                Data Iklan
            </div>
            <div class="card-body">
              <div class="table-responsive ads-table-responsive">
                <table class="table table-striped table-hover ads-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Image</th>
                            <th>Budget</th>
                            <th>Clicks</th>
                            <th>Status</th>
                            <th class="actions-column">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td data-label="ID Iklan">
                            <?php 
                                        
                $advertisers_id = $row['advertisers_id']; 
//echo "<br>advertisers_id: ~".$advertisers_id."~<br>";
    $updateLocalSpending = updateLocalSpending($conn, $advertisers_id);

//echo "<br>updateLocalSpending: ~".$updateLocalSpending."~<br>";

    $updateGlobalSpending = updateGlobalSpending($conn, $advertisers_id);

$id=$row['id'] ;
$providers_domain_url  = $row['providers_domain_url'];
    updateCurrentClick_local($conn, $id, $providers_domain_url ); 

                echo htmlspecialchars($row['id']); 


                            ?></td>
                            <td class="ad-title" data-label="Informasi Iklan"><strong><?php echo htmlspecialchars($row['title_ads']); ?></strong><?php 
        echo "<div class='text-muted small'>".htmlspecialchars($row['providers_name'])."</div>";
        echo "<span class='ad-url' title='".htmlspecialchars($row['landingpage_ads'], ENT_QUOTES)."'>".htmlspecialchars($row['landingpage_ads'])."</span>"; 
        
        $pemilik_iklan = getuser($conn,  $advertisers_id);
        echo "<div class='small mt-2'><span class='data-label'>Advertiser:</span> ".htmlspecialchars((string) $pemilik_iklan)."</div>";

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
        <td data-label="Gambar"><img src="<?php echo htmlspecialchars($row['image_url'], ENT_QUOTES); ?>" alt="Gambar iklan" class="ad-thumbnail" loading="lazy"></td>
<td data-label="Anggaran">

    <div class="text-nowrap"><span class="data-label">Per Klik</span><br><strong>Rp <?php echo number_format($row['budget_per_click_textads'], 2, ',', '.'); ?></strong></div>
    <div class="text-nowrap mt-2"><span class="data-label">Alokasi</span><br>Rp <?php echo number_format($row['budget_allocation'], 2, ',', '.'); ?></div>
    <div class="text-nowrap mt-2"><span class="data-label">Sisa</span><br>Rp <?php echo number_format($remaining_buget, 0, ',', '.'); ?></div>

</td>
                            <td data-label="Jumlah Klik">
            <?php echo htmlspecialchars($total_click); ?></td>
                            <td data-label="Status">
                                <div class="ad-statuses">
                                    <?php if ((int) $row['is_paid'] === 1): ?>
                                        <span class="badge badge-primary"><i class="fas fa-money-check-alt mr-1"></i> Paid</span>
                                    <?php else: ?>
                                        <span class="badge badge-dark"><i class="fas fa-money-bill-wave mr-1"></i> Unpaid</span>
                                    <?php endif; ?>

                                    <?php if ((int) $row['ispublished'] === 1): ?>
                                        <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> Published</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><i class="fas fa-eye-slash mr-1"></i> Unpublished</span>
                                    <?php endif; ?>

                                    <?php if ((int) $row['is_paused'] === 1): ?>
                                        <span class="badge badge-warning"><i class="fas fa-pause-circle mr-1"></i> Paused</span>
                                    <?php else: ?>
                                        <span class="badge badge-light border"><i class="fas fa-play-circle mr-1"></i> Not paused</span>
                                    <?php endif; ?>

                                    <?php if ((int) $row['is_expired'] === 1): ?>
                                        <span class="badge badge-danger"><i class="fas fa-clock mr-1"></i> Expired</span>
                                    <?php else: ?>
                                        <span class="badge badge-info"><i class="fas fa-calendar-check mr-1"></i> Active</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="actions-column" data-label="Aksi"><div class="action-buttons">
                     <a href="#" class="btn btn-info btn-sm" data-toggle="modal" data-target="#detailsModal" 
   data-id="<?php echo $row['id']; ?>"
    data-title="<?php echo htmlspecialchars($row['title_ads'], ENT_QUOTES); ?>"
    data-advertiser_id="<?php echo $row['advertisers_id']; ?>"
data-landingpage="<?php echo htmlspecialchars($row['landingpage_ads'], ENT_QUOTES); ?>"
data-budget="<?php echo $row['budget_per_click_textads']; ?>"
    data-allocation="<?php echo $row['budget_allocation']; ?>"
                                   data-spending="<?php echo $row['current_spending']; ?>"
                                   data-spending-partner="<?php echo $row['current_spending_from_partner']; ?>"
                                   data-updated-spending="<?php echo $row['last_updated_spending']; ?>"
                                   data-published="<?php echo $row['ispublished']; ?>"
                                   data-publish-date="<?php echo $row['published_date']; ?>"
                                   data-paid="<?php echo $row['is_paid']; ?>"
                                   data-paid-date="<?php echo $row['paid_date']; ?>"
                                   data-paid-desc="<?php echo htmlspecialchars($row['paid_desc'], ENT_QUOTES); ?>"
                                   data-total-click="<?php echo $row['total_click']; ?>"
                                   data-current-click="<?php echo $row['current_click']; ?>"
                                   data-current-click-partner="<?php echo $row['current_click_partner']; ?>"
                                   data-expired="<?php echo $row['is_expired']; ?>"
                                   data-expired-date="<?php echo $row['expired_date']; ?>"
                                   data-paused="<?php echo $row['is_paused']; ?>"
                                   data-paused-date="<?php echo $row['paused_date']; ?>">
                                    <i class="fas fa-eye mr-1"></i> View
                                </a>
                                 <a href="#" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#editModal" 
       data-id="<?php echo $row['id']; ?>"
       data-ispublished="<?php echo $row['ispublished']; ?>"
       data-publish-date="<?php echo $row['published_date']; ?>"
     data-is_paid="<?php echo $row['is_paid']; ?>"
   data-paid_date="<?php echo $row['paid_date']; ?>">

        <i class="fas fa-edit mr-1"></i> Edit
    </a>
                            </div></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
              </div>

                <!-- Pagination Links -->
                <nav>
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&amp;<?php echo htmlspecialchars($filter_query); ?>">Sebelumnya</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&amp;<?php echo htmlspecialchars($filter_query); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&amp;<?php echo htmlspecialchars($filter_query); ?>">Berikutnya</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</main>


<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editModalLabel">Edit Publish Status</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="editForm" method="POST" action="update_publish_status.php">
          <?php echo admin_csrf_field(); ?>
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
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
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
        
        <!--<p><strong>Total Clicks:</strong> <span id="total-click"></span></p>-->

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
include("footer.php");
?>

</body>
</html>
