<?php

// view_ads.php
include("db.php");
include("function.php");
include("settings_all.php");

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


// Get user ID and provider domain URL
$this_providers_id = 1;
//$this_providers_domain_url = get_providers_domain_url($conn, $this_providers_id);
$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);

$this_providers_name = getProvidersNameById_JSON("providers_data.json", 1);



// Get search term and filters from GET request
$search = isset($_GET['search']) ? $_GET['search'] : '';
$ispublished = isset($_GET['ispublished']) ? $_GET['ispublished'] : '';

$is_paused= isset($_GET['is_paused']) ? $_GET['is_paused'] : '';
$is_expired = isset($_GET['is_expired']) ? $_GET['is_expired'] : '';

// Get total number of records (for pagination)
$total_sql = "SELECT COUNT(*) FROM advertisers_ads WHERE advertisers_id = ?";

// Add condition for search
if (!empty($search)) {
    $total_sql .= " AND (title_ads LIKE ? OR description_ads LIKE ?)";
}

// Add filters for `ispublished` and `is_expired` if selected
if ($ispublished !== '') {
    $total_sql .= " AND ispublished = ?";
}

if ($is_paused !== '') {
    $total_sql .= " AND is_paused = ?";
}


if ($is_expired !== '') {
    $total_sql .= " AND is_expired = ?";
}

// Prepare and bind statement
$total_stmt = $conn->prepare($total_sql);

// Dynamic binding
$bind_types_total = "i"; // 'i' for advertisers_id
$bind_params_total = [$user_id];
if (!empty($search)) {
    $bind_types_total .= "ss";
    $search_param = "%" . $search . "%";
    $bind_params_total[] = $search_param;
    $bind_params_total[] = $search_param;
}
if ($ispublished !== '') {
    $bind_types_total .= "i";
    $bind_params_total[] = $ispublished;
}

if ($is_paused !== '') {
    $bind_types_total .= "i";
    $bind_params_total[] = $is_paused;
}

if ($is_expired !== '') {
    $bind_types_total .= "i";
    $bind_params_total[] = $is_expired;
}

// Bind and execute total count query
$total_stmt->bind_param($bind_types_total, ...$bind_params_total);
$total_stmt->execute();
$total_stmt->bind_result($total_records);
$total_stmt->fetch();
$total_stmt->close();

// Pagination setup
$limit = 3; // Records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_records / $limit);

// SQL query to fetch ads with search condition and pagination
$sql = "SELECT * FROM advertisers_ads WHERE advertisers_id = ?";

// Add condition for search
if (!empty($search)) {
    $sql .= " AND (title_ads LIKE ? OR description_ads LIKE ?)";
}

// Add filters for `ispublished` and `is_expired` if selected
if ($ispublished !== '') {
    $sql .= " AND ispublished = ?";
}

if ($is_paused !== '') {
    $sql .= " AND is_paused = ?";
}

if ($is_expired !== '') {
    $sql .= " AND is_expired = ?";
}

$sql .= " ORDER BY `last_update` DESC LIMIT ? OFFSET ?";

// Prepare statement
$stmt = $conn->prepare($sql);

// Dynamic binding
$bind_types = "i";
$bind_params = [$user_id];
if (!empty($search)) {
    $bind_types .= "ss";
    $bind_params[] = $search_param;
    $bind_params[] = $search_param;
}
if ($ispublished !== '') {
    $bind_types .= "i";
    $bind_params[] = $ispublished;
}

if ($is_paused !== '') {
    $bind_types .= "i";
    $bind_params[] = $is_paused;
}


if ($is_expired !== '') {
    $bind_types .= "i";
    $bind_params[] = $is_expired;
}

// Add limit and offset for pagination
$bind_types .= "ii";
$bind_params[] = $limit;
$bind_params[] = $offset;

// Bind and execute the query
$stmt->bind_param($bind_types, ...$bind_params);
$stmt->execute();
$result = $stmt->get_result();

// Debug information (optional for testing)
$sql_v = str_replace("advertisers_id = ?", "advertisers_id = '".$user_id."'", $sql);

//echo "<br>sql_v: ".$sql_v;
//echo "<br>user_id: ".$user_id;
//echo "<br>total_records: ".$total_records;
//echo "<br>total_pages: ".$total_pages;
//echo "<br>limit: ".$limit;
//echo "<br>offset: ".$offset;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Ads</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <?php include("main_menu.php") ?>
        <?php include("include_advertiser_menu.php") ?>

        <h1 class="text-center mb-4">Lihat Iklan Anda</h1>

        <!-- Search Form -->
        <form method="GET" action="view_ads.php" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Cari iklan..." value="<?php echo htmlspecialchars(isset($_GET['search']) ? $_GET['search'] : ''); ?>">
                </div>
                <div class="col-md-2">
                    <select name="ispublished" class="form-select">
                        <option value="">Pilih Status Publikasi</option>
                        <option value="1" <?php if (isset($_GET['ispublished']) && $_GET['ispublished'] == '1') echo 'selected'; ?>>Published</option>
                        <option value="0" <?php if (isset($_GET['ispublished']) && $_GET['ispublished'] == '0') echo 'selected'; ?>>Not Published</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <select name="is_paused" class="form-select">
                        <option value="">Pilih Status Pause</option>
                        <option value="1" <?php if (isset($_GET['is_paused']) && $_GET['is_paused'] == '1') echo 'selected'; ?>>Pause</option>
                        <option value="0" <?php if (isset($_GET['is_paused']) && $_GET['is_paused'] == '0') echo 'selected'; ?>>Not Pause</option>
                    </select>
                </div>


                <div class="col-md-2">
                    <select name="is_expired" class="form-select">
                        <option value="">Pilih Status Kadaluarsa</option>
                        <option value="1" <?php if (isset($_GET['is_expired']) && $_GET['is_expired'] == '1') echo 'selected'; ?>>Expired</option>
                        <option value="0" <?php if (isset($_GET['is_expired']) && $_GET['is_expired'] == '0') echo 'selected'; ?>>Not Expired</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <button class="btn btn-primary" type="submit">Cari</button>
                </div>
            </div>
        </form>

        <!-- Display Ads Data in Cards -->
        <div class="row">
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                // Calculate remaining budget
                $remaining_budget = $row['budget_allocation'] - ($row['current_spending'] + $row['current_spending_from_partner']);

                $local_ads_id =  $row['local_ads_id'];
                 $ads_providers_domain_url  =  $row['providers_domain_url'];
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" class="card-img-top" alt="Ad Image">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($row['title_ads']); ?></h5>

                            <a target="_<?php echo $row['id'] ?>" href="preview.php?local_ads_id=<?php echo $row['id'] ?>">PREVIEW</a>
                            <p class="card-text">
                                <?php 
                                // Truncate description to 140 characters
                                if (strlen($row['description_ads']) > 140) {
                                    echo substr(htmlspecialchars($row['description_ads']), 0, 140) . '...'; 
                                ?>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#descriptionModal-<?php echo $row['id']; ?>">Read More</a>
                                <?php
                                } else {
                                    echo htmlspecialchars($row['description_ads']);
                                }

                                $local_ads_id = $row['local_ads_id'];
                                ?>

                            </p>
                            <p><strong>Tampilkan daftar Publisher Local yang menampilkan iklan ini:</strong><a href="view_ads_publishers_mapping.php?local_ads_id=<?php echo $local_ads_id ?>" target="_blank">Publisher display this ads</a>

  <p><strong>Tampilkan daftar Publisher global/partner yang menampilkan iklan ini:</strong><a href="view_ads_publishers_partner_mapping.php?local_ads_id=<?php echo $local_ads_id ?>" target="_blank">Publisher Global/Partner display this ads</a>


                            <p><strong>Landing Page:</strong><?php echo htmlspecialchars($row['landingpage_ads']); ?>

                            


  <p><strong>Budget per Click: Rp </strong> <?php echo number_format($row['budget_per_click_textads'], 0, ',', '.'); ?></p>
                            <p><strong>Budget Allocation: </strong><h2>Rp <?php echo number_format($row['budget_allocation'], 0, ',', '.'); ?></h2></p>

               
<?php
$current_spending = $row['current_spending'];
$current_spending_from_partner = $row['current_spending_from_partner'];


$url_report_local = $this_providers_domain_url."/clicks_ads_local_detail.php?local_ads_id=".$local_ads_id."&click_time=&ads_providers_domain_url=".$this_providers_domain_url;

$url_report_partner = $this_providers_domain_url."/clicks_ads_partner_detail.php?local_ads_id=".$local_ads_id."&click_time=&ads_providers_domain_url=".$this_providers_domain_url;


?>
    
     <p><strong>Current Spending Local: Rp </strong>
                <a target="_" href="<?php echo $url_report_local  ?>"> <?php echo number_format($current_spending); ?></a></p>
                <p><strong>Current Spending from partner: Rp </strong> <a target="_" href="<?php echo $url_report_partner  ?>"><?php echo number_format($current_spending_from_partner); ?></a></p>


                            <p><strong>Remaining Budget:  </strong> <h2>Rp <?php echo number_format($remaining_budget, 0, ',', '.'); ?></h2></p>
                            <p><strong>Last Updated Spending:</strong> <?php echo htmlspecialchars($row['last_updated_spending']); ?></p>

                            <p><strong>Published:</strong> <?php echo $row['ispublished'] ? 'Yes' : 'No'; ?></p>
                            <p><strong>Published Date:</strong> <?php echo htmlspecialchars($row['published_date']); ?></p>
                            <p><strong>Clicks:</strong> <?php echo htmlspecialchars($row['current_click']); ?></p>
                            <p><strong>Partner Clicks:</strong> <?php echo htmlspecialchars($row['current_click_partner']); ?></p>
                            <p><strong>Expired:</strong> <?php echo $row['is_expired'] ? 'Yes' : 'No'; ?></p>
                            <p><strong>Expired Date:</strong> <?php echo htmlspecialchars($row['expired_date']); ?></p>
                            <p><strong>Paused:</strong> <?php echo $row['is_paused'] ? 'Yes' : 'No'; ?></p>
                            <p><strong>Paused Date:</strong> <?php echo htmlspecialchars($row['paused_date']); ?></p>

                            <p>
                                <strong>Paid Status:</strong>
                                <?php if ($row['is_paid']): ?>
                                    <span class="badge bg-success">Paid</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Unpaid</span>
                                <?php endif; ?>
                            </p>

                            <p><strong>local_ads_id:</strong> <?php echo htmlspecialchars($local_ads_id); ?></p>
                            <p>
                                  <p><strong>ads_providers_domain_url:</strong> <?php echo htmlspecialchars($ads_providers_domain_url); ?></p>
                            <p>
                                
                            <!-- Status Display Section -->
                            <p>
                                <strong>Status:</strong>
                                <?php if ($row['ispublished']): ?>
                                    <span class="badge bg-success">Published</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Not Published</span>
                                <?php endif; ?>
                                
                                <?php if ($row['is_paused']): ?>
                                    <span class="badge bg-warning text-dark">Paused</span>
                                <?php else: ?>
                                    <span class="badge bg-primary">Active</span>
                                <?php endif; ?>
                                
                                <?php if ($row['is_expired']): ?>
                                    <span class="badge bg-danger">Expired</span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="card-footer">
                    <?php if ($row['is_paid']==0) {
                        ?>
                         <button class="btn btn-info mb-2" data-bs-toggle="modal" data-bs-target="#paymentModal-<?php echo $row['id']; ?>">Laporan Konfirmasi Pembayaran</button>
                        <?php
                    }
                    ?>

                           


                            <button class="btn btn-info mb-2" data-bs-toggle="modal" data-bs-target="#pauseModal-<?php echo $row['id']; ?>">Pause/Resume</button>
                            
                            <button class="btn btn-info mb-2" data-bs-toggle="modal" data-bs-target="#editModal-<?php echo $row['id']; ?>">Edit Budget dan deskripsi</button>
                             <button class="btn btn-danger mb-2" data-bs-toggle="modal" data-bs-target="#deleteModal-<?php echo $row['id']; ?>">Hapus</button>
                        </div>
                    </div>
                </div>


<!-- Payment Confirmation Modal -->
<div class="modal fade" id="paymentModal-<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="paymentModalLabel-<?php echo $row['id']; ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Laporan Konfirmasi Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="update_paid_desc.php" method="POST">
                    <input type="hidden" name="ad_id" value="<?php echo $row['id']; ?>">
                    <div class="mb-3">
                        <label for="paid_desc" class="form-label">Deskripsi Pembayaran</label>
                        <?php 
                        
    $isipesan="halo admin ".$this_providers_name.", Saya sudah membayar untuk iklan ID: ".$row['id'].", Sebesar Rp ".number_format($row['budget_allocation'],2).", Judul Iklan ".$row['title_ads'].", melalui bank xxxxx pada hari xxxx tanggal-bulan-tahun jam xxxx";                    
$info_pembayaran = str_replace("{{ISIPESAN}}",urlencode($isipesan),$info_pembayaran);
echo nl2br($info_pembayaran) ;

    ?>

                        <textarea class="form-control" name="paid_desc" rows="6" required><?php echo htmlspecialchars($isipesan); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>


                <!-- Delete Confirmation Modal -->
                <div class="modal fade" id="deleteModal-<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel-<?php echo $row['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteModalLabel-<?php echo $row['id']; ?>">Konfirmasi Penghapusan</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <?php
                                // Check if the ad exists in the mapping_advertisers_ads_publishers_site table
                                $conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
                                $local_ads_id = $row['local_ads_id'];
                                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM mapping_advertisers_ads_publishers_site WHERE local_ads_id = ? AND ads_providers_domain_url = ?");
                                $check_stmt->bind_param("is", $local_ads_id, $this_providers_domain_url);
                                $check_stmt->execute();
                                $check_stmt->bind_result($count);
                                $check_stmt->fetch();


                                if ($count > 0): ?>
                                    <p>Iklan ini tidak dapat dihapus karena sudah ditampilkan di situs publisher.</p>
                                <?php else: ?>
                                    <p>Apakah Anda yakin ingin menghapus iklan ini?</p>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <?php if ($count == 0): ?>
                                    <form action="delete_ads.php" method="POST">
                                        <input type="hidden" name="ad_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Hapus</button>
                                    </form>
                                <?php endif; ?>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                 </div>
                        </div>
                    </div>
                </div>

                <!-- Full Description Modal -->
                <div class="modal fade" id="descriptionModal-<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="descriptionModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Deskripsi Lengkap Iklan</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p><?php echo htmlspecialchars($row['description_ads']); ?></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pause/Resume Modal -->
                <div class="modal fade" id="pauseModal-<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="pauseModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Pause/Resume Iklan</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <?php if ($row['is_paused']): ?>
                                    <p>Iklan sedang di-pause. Apakah Anda ingin melanjutkan iklan ini?</p>
                                <?php else: ?>
                                    <p>Iklan sedang aktif. Apakah Anda ingin mem-pause iklan ini?</p>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <form action="pause_resume_ads.php" method="POST">
                                    <input type="hidden" name="ad_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn btn-warning">
                                        <?php echo $row['is_paused'] ? 'Resume Iklan' : 'Pause Iklan'; ?>
                                    </button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            
            <!-- Edit Modal -->
<div class="modal fade" id="editModal-<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Iklan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="edit_ads.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="ad_id" value="<?php echo $row['id']; ?>">
                    <input type="hidden" name="existing_image_url" value="<?php echo htmlspecialchars($row['image_url']); ?>"> <!-- Hidden input for existing image URL -->
    

                    <div class="mb-3">
                        <label for="title_ads" class="form-label">Judul Iklan</label>
                        <input type="text" class="form-control" name="title_ads" value="<?php echo htmlspecialchars($row['title_ads']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description_ads" class="form-label">Deskripsi. maksimal 250 charcters.</label>
                        <textarea class="form-control" name="description_ads" required><?php echo htmlspecialchars($row['description_ads']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="landingpage_ads" class="form-label">Landing Page</label>
                        <input type="text" class="form-control" name="landingpage_ads" value="<?php echo htmlspecialchars($row['landingpage_ads']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="image_url" class="form-label">Upload Gambar Baru (JPG/PNG max 5MB)</label>
                        <input type="file" class="form-control" name="image_url">

                    </div>
                    <div class="mb-3">
                        <label for="budget_per_click_textads" class="form-label">Budget per Click</label>
                        <input type="number" class="form-control" name="budget_per_click_textads" value="<?php echo htmlspecialchars($row['budget_per_click_textads']); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>
</div>





            <?php endwhile; ?>
        </div>
    </div>


        <!-- Pagination -->
        <nav aria-label="Page navigation example">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&ispublished=<?php echo urlencode($ispublished); ?>&is_expired=<?php echo urlencode($is_expired); ?>">Previous</a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&ispublished=<?php echo urlencode($ispublished); ?>&is_expired=<?php echo urlencode($is_expired); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&ispublished=<?php echo urlencode($ispublished); ?>&is_expired=<?php echo urlencode($is_expired); ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <!-- Bootstrap JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
