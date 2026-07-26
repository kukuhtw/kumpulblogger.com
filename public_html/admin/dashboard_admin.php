<?php
/*
admin/dashboard_admin.php
*/
// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    // If not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

// Include the database connection if you need to fetch any data
include("../db.php");

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

// Fetch admin data if needed (optional)
$loginemail_admin = $_SESSION['loginemail_admin'];
// Example: Fetch admin's last login time
$sql = "SELECT last_login FROM msadmin WHERE loginemail = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $loginemail_admin);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Close statement and connection
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <?php include("style_toogle.php"); ?>
</head>

<body>

<div class="admin-navbar">
    <a class="brand" href="dashboard_admin.php">Admin Dashboard</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
</div>

<?php include("sidebar_menu.php");?>

<main class="admin-main" id="mainContent">
    <div class="mb-4">
        <h1 class="page-title">Dashboard Overview</h1>
        <p class="page-subtitle">Selamat datang kembali di panel administrasi KumpulBlogger.</p>
    </div>
    <div class="card">
        <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between">
            <div>
                <div class="data-label">Login terakhir</div>
                <div class="font-weight-bold"><?php echo htmlspecialchars($admin['last_login'] ?? '-'); ?></div>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="manage_users.php" class="btn btn-success">Kelola Pengguna</a>
                <a href="manage_writer_quotas.php" class="btn btn-primary ml-2">Quota Menulis</a>
            </div>
        </div>
    </div>
</main>

<?php include("js_toogle.php"); ?>

<?php include("footer.php");?>

</body>
</html>

