<?php
include("db.php");
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


// Pagination setup
$limit = 40; // Number of results per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Get current page number
$offset = ($page - 1) * $limit; // Offset for SQL query

// Search setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Sorting setup
$sort_order = isset($_GET['sort']) && $_GET['sort'] === 'desc' ? 'DESC' : 'ASC';

// Prepare SQL query with search and sorting
$sql = "SELECT * FROM publishers_site WHERE (site_name LIKE ? OR site_domain LIKE ? OR site_desc LIKE ?)
        ORDER BY rate_text_ads $sort_order LIMIT ? OFFSET ?";

// Prepare the statement
$stmt = $conn->prepare($sql);

// Add search wildcard
$search_param = '%' . $search . '%';

// Bind parameters: 3 search fields and pagination limits
$stmt->bind_param('sssii', $search_param, $search_param, $search_param, $limit, $offset);

// Execute the query
$stmt->execute();
$result = $stmt->get_result();

// Count total records for pagination
$count_sql = "SELECT COUNT(*) FROM publishers_site WHERE (site_name LIKE ? OR site_domain LIKE ? OR site_desc LIKE ?)";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param('sss', $search_param, $search_param, $search_param);
$count_stmt->execute();
$count_stmt->bind_result($total_records);
$count_stmt->fetch();
$total_pages = ceil($total_records / $limit); // Calculate total pages

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publisher Sites</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
          <?php include("main_menu.php") ?>
        <?php include("include_advertiser_menu.php") ?>


        <h1 class="text-center mb-4">List of Publisher Sites</h1>

        <!-- Search Form -->
        <form class="mb-4" method="GET" action="view_rate_publisher.php">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search by site name, domain, or description..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
        </form>

        <!-- Sort Button -->
        <div class="text-end mb-3">
            <a href="view_rate_publisher.php?search=<?php echo htmlspecialchars($search); ?>&sort=<?php echo $sort_order === 'ASC' ? 'desc' : 'asc'; ?>" class="btn btn-secondary">
                Sort by Rate <?php echo $sort_order === 'ASC' ? 'Descending' : 'Ascending'; ?>
            </a>
        </div>

        <!-- Display Publishers Table -->
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Site Name</th>
                    <th>Domain</th>
                    <th>Description</th>
                    <th>jenis iklan</th>
                    <th>Rate per Click (Text Ads)</th>
                    <th>Harga Jual per Click (Text Ads)</th>
                    <th>Registration Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['site_name']); ?>
                                <br>providers_name: <?php echo htmlspecialchars($row['providers_name']); ?>

                                
                            </td>
                            <td><a href="<?php echo htmlspecialchars($row['site_domain']); ?>" target="_blank"><?php echo htmlspecialchars($row['site_domain']); ?></a></td>
                            <td><?php echo htmlspecialchars($row['site_desc']); ?></td>

                            <td><?php 
                            $site_desc = $row['site_desc'];
                             $advertiser_allowed = 
                             $row['advertiser_allowed'];
                              $advertiser_rejected = $row['advertiser_rejected'];

                            echo "Deskripsi: ".htmlspecialchars($site_desc);
                             echo "<br>Diijinkan: ". htmlspecialchars($advertiser_allowed);  
                             echo "<br>Tidak Diijinkan: ". htmlspecialchars($advertiser_rejected); 

                            ?>
                                

                            </td>

                            <td><?php 
                             $rate_text_ads = $row['rate_text_ads'];
                            echo "Rp ". number_format($rate_text_ads,1); 

                        ?></td>
                            <td><?php 
                           
                            $rate_text_ads_with_markup_local = $rate_text_ads + ($rate_text_ads/2);


                            echo "Rp ".number_format($rate_text_ads_with_markup_local,1); 

                            ?>
                                

                            </td>

                            <td><?php echo htmlspecialchars($row['regdate']); ?></td>
                            <td>
                                <?php if ($row['isbanned']): ?>
                                    <span class="badge bg-danger">Banned</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Active</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No results found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="view_rate_publisher.php?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search); ?>&sort=<?php echo htmlspecialchars($sort_order); ?>">
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
