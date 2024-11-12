<?php
/*
admin/manage_users.php
*/
// Start session
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
include("../function_publisher.php");

$loginemail_admin = $_SESSION['loginemail_admin'];
// Set default page number and items per page
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search functionality
$search_query = "";
if (isset($_GET['search'])) {
    $search_query = trim($_GET['search']);
}

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

// Build search SQL query
$search_sql = "";
if ($search_query != "") {
    $search_sql = " WHERE loginemail LIKE ? OR realname LIKE ? OR whatsapp LIKE ?";
}

// Get total number of users
$sql = "SELECT COUNT(*) AS total FROM msusers" . $search_sql;
$stmt = $conn->prepare($sql);
if ($search_query != "") {
    $like_search = '%' . $search_query . '%';
    $stmt->bind_param("sss", $like_search, $like_search, $like_search);
}
$stmt->execute();
$result = $stmt->get_result();
$total_users = $result->fetch_assoc()['total'];

// Fetch users for the current page
$sql = "SELECT * FROM msusers" . $search_sql . " ORDER BY local_revenue_unpaid DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($search_query != "") {
    $stmt->bind_param("sssii", $like_search, $like_search, $like_search, $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

// Calculate total pages
$total_pages = ceil($total_users / $limit);

// Close the connection
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User List</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <?php include("style_toogle.php"); ?>
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

<div class="navbar">
    Admin Dashboard
    <a href="logout.php" style="float:right;">Logout</a>
</div>

<?php include("sidebar_menu.php"); ?>

<div class="container" id="mainContent"> 

    <h2>User List</h2>

    <!-- Search Form -->
    <form method="GET" action="manage_users.php" class="form-inline mb-3">
        <input type="text" name="search" class="form-control mr-2" placeholder="Search users" value="<?php echo htmlspecialchars($search_query); ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <div class="card">
        <div class="card-header">
            User Data
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Real Name</th>
                        <th>WhatsApp</th>
                        <th>Last Login</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): 
                        $user_id_ = $row['id'];
                        $countTotalAds = countTotalAds($conn, $user_id_);
                        $updateLocalSpending = updateLocalSpending($conn, $user_id_);
                        $updateGlobalSpending = updateGlobalSpending($conn, $user_id_);
                        $countTotalWebsites = countTotalWebsites($conn, $user_id_);
                        $updateLocalRevenue = updateLocalRevenue($conn, $user_id_);
                        $updateGlobalRevenue = updateGlobalRevenue($conn, $user_id_);
                        $total_revenue = $updateLocalRevenue + $updateGlobalRevenue;

                        $r = "<a href='rekap_user_local_click.php?user_id=".$user_id_."'>Detail</a>";
                        $email_pubs = $row['loginemail'];
                        updateRevenueForUser($conn ,$email_pubs);
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($row['loginemail']); ?>
                            <br>countTotalAds: <?php echo $countTotalAds; ?>
                            <br>updateLocalSpending: Rp <?php echo number_format($updateLocalSpending, 2); ?>
                            <br>updateGlobalSpending: Rp <?php echo number_format($updateGlobalSpending, 2); ?>
                            <br>countTotalWebsites: <?php echo number_format($countTotalWebsites); ?>
                            <br>updateLocalRevenue: Rp <?php echo number_format($updateLocalRevenue); ?>
                            <br>updateGlobalRevenue: Rp <?php echo number_format($updateGlobalRevenue); ?>
                            <br>total_revenue: Rp <?php echo number_format($total_revenue); ?>
                            <br><?php echo $r; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($row['realname']); ?><br>
                            Bank: <?php echo htmlspecialchars($row['bank']); ?><br>
                            Account Name: <?php echo htmlspecialchars($row['account_name']); ?><br>
                            Account Number: <?php echo htmlspecialchars($row['account_number']); ?><br>
                            <br><strong>Revenue:</strong><br>
                            Local Revenue Paid: <?php echo number_format($row['local_revenue_paid'], 2); ?><br>
                            Local Revenue Unpaid: <?php echo number_format($row['local_revenue_unpaid'], 2); ?><br>
                            Current Revenue from Partner: <?php echo number_format($row['current_revenue_from_partner'], 2); ?><br>
                            Partner Revenue Paid: <?php echo number_format($row['partner_revenue_paid'], 2); ?><br>
                            Partner Revenue Unpaid: <?php echo number_format($row['partner_revenue_unpaid'], 2); ?><br>
                            Total Current Revenue: <?php echo number_format($row['total_current_revenue'], 2); ?><br>
                            <br><strong>Spending:</strong><br>
                            Current Spending: <?php echo number_format($row['current_spending'], 2); ?><br>
                            Current Spending from Partner: <?php echo number_format($row['current_spending_from_partner'], 2); ?><br>
                            Total Current Spending: <?php echo number_format($row['total_current_spending'], 2); ?><br>
                        </td>
                        <td><?php echo htmlspecialchars($row['whatsapp']); ?></td>
                        <td><?php echo htmlspecialchars($row['last_login']); ?></td>
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
    </div>
</div>

<?php 
$conn->close();
include("footer.php"); 
include("js_toogle.php"); 
?>

</body>
</html>
