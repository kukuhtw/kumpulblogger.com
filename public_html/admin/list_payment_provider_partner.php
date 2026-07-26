<?php
// list_payment_provider_partner.php
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

// Pagination settings
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Get the current page number
$offset = ($page - 1) * $limit; // Calculate the offset for the SQL query

// Prepare the query to fetch data from payment_partner_providers with limit and offset
$sql = "SELECT * FROM `payment_partner_providers` ORDER BY payment_date DESC LIMIT ?, ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();

// Count the total number of records in the table for pagination
$count_sql = "SELECT COUNT(*) AS total FROM `payment_partner_providers`";
$count_result = $mysqli->query($count_sql);
$count_row = $count_result->fetch_assoc();
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit); // Calculate total number of pages
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Partner Pubs List</title>
    <?php include("style_toogle.php"); ?>
</head>
<body>

<div class="admin-navbar">
    <a class="brand" href="dashboard_admin.php">Admin Dashboard</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
</div>

<?php include("sidebar_menu.php"); ?>
<main class="admin-main" id="mainContent">
    <h2>Payment Records</h2>
    <div class="table-responsive">
    <table class="table table-bordered table-hover mt-3">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Email - Provider</th>
                <th>Nominal</th>
                <th>Description</th>
                <th>Payment Date</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['email_provider']); ?> - <?php echo htmlspecialchars($row['partner_providers_domain_url']); ?></td>
                    <td><?php echo number_format($row['nominal'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['payment_description']); ?></td>
                    <td><?php echo htmlspecialchars($row['payment_date']); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" class="text-center">No records found.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>

    <!-- Pagination -->
    <nav>
        <ul class="pagination justify-content-center">
            <?php if ($total_pages > 1): ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="list_payment_provider_partner.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            <?php endif; ?>
        </ul>
    </nav>
</main>

<?php
$stmt->close();
$mysqli->close();
?>

<?php include("js_toogle.php"); ?>

<?php include("footer.php"); ?>

</body>
</html>
