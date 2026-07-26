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
$status_updated = isset($_GET['status']) && $_GET['status'] === 'success';



// Get user ID and provider domain URL
$this_providers_id = 1;
$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);


// Pagination setup
$limit = 12; // Results per page
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
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
$url_report_local = $this_providers_domain_url . "/clicks_ads_local_detail.php?local_ads_id=" . $local_ads_id . "&click_time=&ads_providers_domain_url=" . urlencode($this_providers_domain_url);
$url_report_partner = $this_providers_domain_url . "/clicks_ads_partner_detail.php?local_ads_id=" . $local_ads_id . "&click_time=&ads_providers_domain_url=" . urlencode($this_providers_domain_url);

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
$total_pages = max(1, (int) ceil($total_records / $limit));
$window_start = max(1, $page - 2);
$window_end = min($total_pages, $page + 2);
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
    <style>
        body { background: #f5f7fa; color: #26313c; }
        .page-shell { max-width: 1240px; }
        .page-heading h1 { margin: 0; font-size: 1.65rem; font-weight: 750; }
        .page-heading p { margin: .3rem 0 0; color: #6c757d; }
        .ad-summary { overflow: hidden; border: 0; border-radius: .9rem; box-shadow: 0 3px 14px rgba(31,41,55,.08); }
        .ad-summary-image { width: 100%; height: 100%; min-height: 250px; object-fit: cover; background: #e9ecef; }
        .ad-summary-details { display: grid; grid-template-columns: 1fr 1fr; gap: .65rem; }
        .summary-item { padding: .75rem; border-radius: .6rem; background: #f7f9fb; }
        .summary-label { display: block; color: #6c757d; font-size: .7rem; font-weight: 700; text-transform: uppercase; }
        .summary-value { display: block; margin-top: .2rem; font-weight: 700; overflow-wrap: anywhere; }
        .remaining-budget { background: #eaf8f0; color: #12683a; }
        .sort-panel { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; }
        .sort-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
        .mapping-card { border: 0; border-radius: .85rem; box-shadow: 0 3px 14px rgba(31,41,55,.08); }
        .mapping-card .card-body { display: flex; flex-direction: column; }
        .mapping-card .card-body > p { display: none; }
        .mapping-title { font-size: 1.08rem; font-weight: 750; }
        .mapping-domain { margin-bottom: 1rem; font-size: .8rem; overflow-wrap: anywhere; }
        .mapping-details { display: grid; gap: .4rem; margin-bottom: 1rem; }
        .mapping-row { display: flex; justify-content: space-between; gap: 1rem; padding-bottom: .4rem; border-bottom: 1px dashed #e2e7ec; font-size: .82rem; }
        .mapping-row span:last-child, .mapping-row strong { max-width: 65%; text-align: right; overflow-wrap: anywhere; }
        .status-list { display: flex; flex-wrap: wrap; gap: .35rem; margin-bottom: 1rem; }
        .mapping-card .btn { margin-top: auto; }
        .mapping-pagination { flex-wrap: wrap; gap: .25rem; }
        .mapping-pagination .page-link { min-width: 38px; border-radius: .45rem !important; text-align: center; }
        @media (max-width: 767.98px) { .ad-summary-details { grid-template-columns: 1fr; } .ad-summary-image { height: 220px; min-height: 0; } }
        @media (max-width: 575.98px) { .page-shell { padding-right: .75rem; padding-left: .75rem; } .page-heading h1 { font-size: 1.4rem; } .sort-actions .btn { flex: 1 1 100%; } }
    </style>
</head>
<body>
    <div class="container page-shell py-3 py-md-4">
        <?php include("main_menu.php") ?>
        <?php include("include_advertiser_menu.php") ?>

        <?php if ($status_updated): ?><div class="alert alert-success">Status persetujuan berhasil diperbarui.</div><?php endif; ?>

        <div class="page-heading mb-4">
            <h1>Mapping Publisher Iklan</h1>
            <p>Daftar site publisher lokal yang menampilkan iklan #<?php echo htmlspecialchars($local_ads_id); ?>.</p>
        </div>

  <!-- Display Advertisers Ads Info -->
        <div class="card ad-summary mb-4">
          <div class="row g-0">
           <div class="col-12 col-md-4"><img class="ad-summary-image" src="<?php echo htmlspecialchars($image_url, ENT_QUOTES); ?>" alt="Gambar iklan"></div>
           <div class="col-12 col-md-8"><div class="card-body p-4">
            <h2 class="h4 fw-bold"><?php echo htmlspecialchars($title_ads); ?></h2>
            <p class="text-muted"><?php echo htmlspecialchars($description_ads); ?></p>
            <a class="btn btn-sm btn-outline-primary align-self-start mb-3" href="<?php echo htmlspecialchars($landingpage_ads, ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt me-1"></i> Landing Page</a>
            <div class="ad-summary-details">
              <div class="summary-item"><span class="summary-label">Budget per Klik</span><span class="summary-value">Rp <?php echo number_format((float) $budget_per_click_textads, 0, ',', '.'); ?></span></div>
              <div class="summary-item"><span class="summary-label">Alokasi Budget</span><span class="summary-value">Rp <?php echo number_format((float) $budget_allocation, 0, ',', '.'); ?></span></div>
              <div class="summary-item"><span class="summary-label">Spending Lokal</span><a class="summary-value" href="<?php echo htmlspecialchars($url_report_local, ENT_QUOTES); ?>">Rp <?php echo number_format((float) $current_spending, 0, ',', '.'); ?></a></div>
              <div class="summary-item"><span class="summary-label">Spending Partner</span><a class="summary-value" href="<?php echo htmlspecialchars($url_report_partner, ENT_QUOTES); ?>">Rp <?php echo number_format((float) $current_spending_from_partner, 0, ',', '.'); ?></a></div>
              <div class="summary-item"><span class="summary-label">Total Spending</span><span class="summary-value">Rp <?php echo number_format((float) $total_spending_local_and_partner, 0, ',', '.'); ?></span></div>
              <div class="summary-item remaining-budget"><span class="summary-label">Sisa Budget</span><span class="summary-value">Rp <?php echo number_format((float) $remaining_buget, 0, ',', '.'); ?></span></div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3"><span class="badge <?php echo $ispublished ? 'text-bg-success' : 'text-bg-secondary'; ?>"><?php echo $ispublished ? 'Published' : 'Unpublished'; ?></span><span class="small text-muted">Diperbarui: <?php echo htmlspecialchars($last_updated_spending ?: '-'); ?></span></div>
           </div></div>
          </div>
          <div class="d-none">
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
        </div>


        <!-- Sorting Options -->
        <div class="sort-panel mb-3">
            <div><strong><?php echo number_format($total_records); ?> publisher ditemukan</strong></div>
          <div class="sort-actions">
            <a href="view_ads_publishers_mapping.php?sort=rate_text_ads&order=<?php echo $sort_order === 'ASC' ? 'desc' : 'asc'; ?><?php echo $local_ads_id ? '&local_ads_id=' . $local_ads_id : ''; ?>" class="btn btn-secondary">
                Sort by Rate (<?php echo $sort_order === 'ASC' ? 'Desc' : 'Asc'; ?>)
            </a>
            <a href="view_ads_publishers_mapping.php?sort=budget_per_click_textads&order=<?php echo $sort_order === 'ASC' ? 'desc' : 'asc'; ?><?php echo $local_ads_id ? '&local_ads_id=' . $local_ads_id : ''; ?>" class="btn btn-secondary">
                Sort by Budget per Click (<?php echo $sort_order === 'ASC' ? 'Desc' : 'Asc'; ?>)
            </a>
          </div>
        </div>

        
        
        <!-- Display Data in Cards -->
        <div class="row g-4">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card mapping-card h-100">
                            <div class="card-body">
                                <h2 class="mapping-title"><?php echo htmlspecialchars($row['site_name']); ?></h2>
                                <a class="mapping-domain" href="<?php echo htmlspecialchars($row['site_domain'], ENT_QUOTES); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($row['site_domain']); ?></a>
                                <div class="mapping-details">
                                    <div class="mapping-row"><span>Publisher Site ID</span><strong><?php echo htmlspecialchars($row['publishers_site_local_id']); ?></strong></div>
                                    <div class="mapping-row"><span>Provider</span><span><?php echo htmlspecialchars($row['pubs_providers_domain_url']); ?></span></div>
                                    <div class="mapping-row"><span>Rate Publisher</span><strong>Rp <?php echo number_format((float) $row['rate_text_ads'], 0, ',', '.'); ?></strong></div>
                                    <div class="mapping-row"><span>Budget per Klik</span><strong>Rp <?php echo number_format((float) $row['budget_per_click_textads'], 0, ',', '.'); ?></strong></div>
                                </div>
                                <div class="status-list">
                                    <span class="badge <?php echo $row['is_published'] ? 'text-bg-success' : 'text-bg-secondary'; ?>"><?php echo $row['is_published'] ? 'Published' : 'Unpublished'; ?></span>
                                    <span class="badge <?php echo $row['is_paused'] ? 'text-bg-warning' : 'text-bg-primary'; ?>"><?php echo $row['is_paused'] ? 'Paused' : 'Berjalan'; ?></span>
                                    <span class="badge <?php echo $row['is_expired'] ? 'text-bg-danger' : 'text-bg-info'; ?>"><?php echo $row['is_expired'] ? 'Expired' : 'Aktif'; ?></span>
                                    <span class="badge <?php echo $row['is_approved_by_publisher'] ? 'text-bg-success' : 'text-bg-danger'; ?>">Publisher: <?php echo $row['is_approved_by_publisher'] ? 'Setuju' : 'Tolak'; ?></span>
                                    <span class="badge <?php echo $row['is_approved_by_advertiser'] ? 'text-bg-success' : 'text-bg-danger'; ?>">Anda: <?php echo $row['is_approved_by_advertiser'] ? 'Setuju' : 'Tolak'; ?></span>
                                </div>
                                <div class="d-none">

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
                                </div>


                <!-- Advertiser Approve/Reject Buttons -->
                        <div class="d-flex justify-content-between">
                            <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#editApprovalModal<?php echo $row['id']; ?>"><i class="fas fa-edit me-1"></i> Ubah Persetujuan</button>
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
        <div class="text-center text-muted small mt-4">Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?></div>
        <nav class="mt-2" aria-label="Navigasi mapping publisher">
            <ul class="pagination mapping-pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="?local_ads_id=<?php echo $local_ads_id; ?>&amp;sort=<?php echo urlencode($sort_by); ?>&amp;order=<?php echo strtolower($sort_order); ?>&amp;page=<?php echo max(1, $page - 1); ?>">&laquo;</a></li>
                <?php if ($window_start > 1): ?>
                    <li class="page-item"><a class="page-link" href="?local_ads_id=<?php echo $local_ads_id; ?>&amp;sort=<?php echo urlencode($sort_by); ?>&amp;order=<?php echo strtolower($sort_order); ?>&amp;page=1">1</a></li>
                    <?php if ($window_start > 2): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
                <?php endif; ?>
                <?php for ($i = $window_start; $i <= $window_end; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link" href="?local_ads_id=<?php echo $local_ads_id; ?>&amp;sort=<?php echo urlencode($sort_by); ?>&amp;order=<?php echo strtolower($sort_order); ?>&amp;page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>
                <?php if ($window_end < $total_pages): ?>
                    <?php if ($window_end < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
                    <li class="page-item"><a class="page-link" href="?local_ads_id=<?php echo $local_ads_id; ?>&amp;sort=<?php echo urlencode($sort_by); ?>&amp;order=<?php echo strtolower($sort_order); ?>&amp;page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a></li>
                <?php endif; ?>
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"><a class="page-link" href="?local_ads_id=<?php echo $local_ads_id; ?>&amp;sort=<?php echo urlencode($sort_by); ?>&amp;order=<?php echo strtolower($sort_order); ?>&amp;page=<?php echo min($total_pages, $page + 1); ?>">&raquo;</a></li>
            </ul>
        </nav>
    </div>

    <!-- Bootstrap JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
