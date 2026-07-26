<?php

// clicks_publisher_detail.php

// Database connection
include("db.php"); // Koneksi database
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

// Get parameters from GET request
$pub_id = isset($_GET['pub_id']) ? intval($_GET['pub_id']) : 0;
$pubs_providers_domain_url = isset($_GET['pubs_providers_domain_url']) ? $_GET['pubs_providers_domain_url'] : '';

if ($pub_id === 0 || empty($pubs_providers_domain_url)) {
    die("Invalid parameters.");
}

// Pagination settings
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Prepare the dynamic SQL query
$sql = "SELECT pub_id, pub_provider, ip_address, browser_agent, referrer, click_time, local_ads_id, ads_providers_name, title_ads, landingpage_ads, revenue_publishers, site_name, site_domain 
        FROM ad_clicks 
        WHERE pub_id = ? 
        AND pubs_providers_domain_url = ? 
        AND isaudit = 1 
        AND is_reject = 0
        ORDER BY click_time DESC 
        LIMIT ?, ?";

// Prepare the SQL statement
$stmt = $mysqli->prepare($sql);

// Bind the parameters
$stmt->bind_param("isii", $pub_id, $pubs_providers_domain_url, $offset, $limit);

// Execute the query
$stmt->execute();

// Get the result
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Click Publisher Detail</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
      <?php include("main_menu.php"); ?>
    <?php include("include_publisher_menu.php"); ?>
    <h2 class="mb-4">Click Publisher Details</h2>
    <?php if ($result->num_rows > 0) : ?>
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>Publisher ID</th>
                    <th>IP Address</th>
                    <th>Browser Agent</th>
                    <th>Referrer</th>
                    <th>Click Time</th>
                    <th>Ad ID</th>
                    <th>Ad Provider</th>
                    <th>Title Ads</th>
                    <th>Landing Page Ads</th>
                    <th>Revenue</th>
                    <th>Site Name</th>
                    <th>Site Domain</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['pub_id']); 

                    echo "<br>". htmlspecialchars($row['site_domain']);

                ?><br><?php echo htmlspecialchars($row['pub_provider']); ?></td>
                    <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                    <td><?php echo htmlspecialchars($row['browser_agent']); ?></td>
                    <td><?php echo htmlspecialchars($row['referrer']); ?></td>
                    <td><?php echo htmlspecialchars($row['click_time']); ?></td>
                    <td><?php echo htmlspecialchars($row['local_ads_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['ads_providers_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['title_ads']); ?></td>
                    <td><a href="<?php echo htmlspecialchars($row['landingpage_ads']); ?>" target="_blank"><?php echo htmlspecialchars($row['landingpage_ads']); ?></a></td>
                    <td><?php echo htmlspecialchars($row['revenue_publishers']); ?></td>
                    <td><?php echo htmlspecialchars($row['site_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['site_domain']); ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else : ?>
        <div class="alert alert-warning">No records found.</div>
    <?php endif; ?>

    <?php
    // Prepare the count query to calculate total pages
    $count_sql = "SELECT COUNT(*) as total 
                  FROM ad_clicks 
                  WHERE pub_id = ? 
                  AND pubs_providers_domain_url = ? 
                  AND isaudit = 1 
                  AND is_reject = 0";

    $count_stmt = $mysqli->prepare($count_sql);
    $count_stmt->bind_param("is", $pub_id, $pubs_providers_domain_url);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $total_records = $count_row['total'];

    // Calculate total pages
    $total_pages = ceil($total_records / $limit);
    ?>

    <!-- Pagination Links -->
    <nav>
        <ul class="pagination justify-content-center">
            <?php if ($total_pages > 1) : ?>
                <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                    <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="clicks_publisher_detail.php?pub_id=<?php echo $pub_id; ?>&pubs_providers_domain_url=<?php echo $pubs_providers_domain_url; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the statement and connection
$stmt->close();
$count_stmt->close();
$mysqli->close();
?>
