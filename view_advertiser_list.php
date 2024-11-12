<?php
// view_advertiser_list.php

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

// Get provider domain URL (you might have a function to get this)
$this_providers_id = 1;
//$this_providers_domain_url = get_providers_domain_url($conn, $this_providers_id);
$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);


// Pagination and Searching Setup
$limit = 20; // Maximum results per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number
$offset = ($page - 1) * $limit; // Offset for the SQL query

$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Sorting Setup
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'budget_per_click_textads';
$sort_order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

// Search query with pagination, searching, and sorting
$stmt = $conn->prepare("SELECT id, local_ads_id, title_ads, description_ads, landingpage_ads, image_url, budget_per_click_textads, ispublished, published_date, is_expired, expired_date
                        FROM advertisers_ads
                        WHERE ispublished = 1 AND (title_ads LIKE ? OR description_ads LIKE ?)
                        ORDER BY $sort_by $sort_order
                        LIMIT ? OFFSET ?");

$search_param = "%" . $search_query . "%";
$stmt->bind_param("ssii", $search_param, $search_param, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Get total records count for pagination
$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM advertisers_ads WHERE ispublished = 1 AND (title_ads LIKE ? OR description_ads LIKE ?)");
$count_stmt->bind_param("ss", $search_param, $search_param);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertiser Ads List</title>
    <!-- Include Bootstrap for styling (optional) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Local Ads</h1>
        <?php include("main_menu.php") ?>
        <?php include("include_publisher_menu.php") ?>

        <!-- Search Form -->
        <form method="GET" action="" class="mb-4">
            <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Search ads..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>


        



<?php if ($result->num_rows > 0): ?>
            <div class="row">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($row['image_url']); ?>" class="card-img-top" alt="Ad Image" style="height: 200px; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($row['title_ads']); ?></h5>
                                <p class="card-text"><strong>Description:</strong> <?php echo htmlspecialchars($row['description_ads']); ?></p>
                                <p><strong>Landing Page:</strong> <a href="<?php echo htmlspecialchars($row['landingpage_ads']); ?>" target="_blank"><?php echo htmlspecialchars($row['landingpage_ads']); ?></a></p>
                                <p><strong>Budget per Click:</strong> <?php echo htmlspecialchars($row['budget_per_click_textads']); ?></p>
                                <p><strong>Published:</strong> <?php echo $row['ispublished'] ? 'Yes' : 'No'; ?></p>
                                <p><strong>Published Date:</strong> <?php echo htmlspecialchars($row['published_date']); ?></p>
                                <p><strong>Expired:</strong> <?php echo $row['is_expired'] ? 'Yes' : 'No'; ?></p>
                                <p><strong>Expired Date:</strong> <?php echo htmlspecialchars($row['expired_date']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination Links -->
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search_query); ?>&sort=<?php echo htmlspecialchars($sort_by); ?>&order=<?php echo htmlspecialchars($sort_order); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php else: ?>
            <p class="text-center">No ads found.</p>
        <?php endif; ?>
    </div>

    <!-- Include Bootstrap JS (optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        
</body>
</html>
