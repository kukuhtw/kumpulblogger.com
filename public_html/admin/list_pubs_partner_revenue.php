<?php
// list_pubs_partner_revenue.php
session_start();

// Include the database connection
include("../db.php");
include("function_admin.php");

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    // If not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

// Fetch login email from session
$loginemail_admin = $_SESSION['loginemail_admin'];

// Database connection using MySQLi
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit("Database connection failed.");
}

// Pagination setup
$limit = 10; // Limit 10 records per page
if (isset($_GET['page'])) {
    $page = $_GET['page'];
} else {
    $page = 1;
}

$offset = ($page - 1) * $limit;

// Get total records
$total_query = "SELECT COUNT(*) as total FROM rekap_total_publisher_partner";
$total_result = $mysqli->query($total_query);
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $limit);

// Fetch records for the current page
$query = "SELECT pub_id, site_name, site_domain, pubs_providers_domain_url, total_revenue_publishers, total_clicks, rekap_date 
          FROM rekap_total_publisher_partner 
          LIMIT $limit OFFSET $offset";
$result = $mysqli->query($query);

// HTML & Bootstrap Layout
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Publisher Partner Revenue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
     <?php include("style_toogle.php") ?>;
    <style>
        body {
            background-color: #f8f9fa;
            position: relative;
            min-height: 100vh;
        }
        .navbar {
            background-color: #343a40;
            color: white;
            padding: 10px;
            font-size: 18px;
            font-weight: bold;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }
        .sidebar {
            background-color: #343a40;
            padding: 20px;
            height: 100vh;
            position: fixed;
            color: white;
        }
        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }
        .sidebar ul li a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: white;
        }
        .sidebar ul li a:hover {
            background-color: #575757;
        }
        .container {
            margin-left: 250px;
            padding: 20px;
        }
        .card {
            margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #28a745;
            color: white;
            font-size: 24px;
            text-align: center;
        }
        .footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px;
            position: absolute;
            bottom: 0;
            width: 100%;
        }
        .table {
            margin-top: 20px;
        }
        .pagination {
            margin-top: 20px;
            justify-content: center;
        }
    </style>
</head>
<body>

<?php include("sidebar_menu.php"); ?>

<div class="container" id="mainContent">
        
  

    <div class="content">
        
    <h2 class="mb-4">List Publisher Partner Revenue</h2>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Publisher ID</th>
                <th>Site Name</th>
                <th>Site Domain</th>
                <th>Provider Domain URL</th>
                <th>Total Revenue Publishers</th>
                <th>Total Clicks</th>
                <th>Rekap Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0) : ?>
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo $row['pub_id']; ?></td>
                        <td><?php echo $row['site_name']; ?></td>
                        <td><?php echo $row['site_domain']; ?></td>
                        <td><?php echo $row['pubs_providers_domain_url']; ?></td>
                        <td><?php echo $row['total_revenue_publishers']; ?></td>
                        <td><?php echo $row['total_clicks']; ?></td>
                        <td><?php echo $row['rekap_date']; ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7">No records found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <nav>
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>


<?php include("js_toogle.php"); ?>

</body>
</html>

<?php
// Close connection
$mysqli->close();
?>
