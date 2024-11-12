<?php

// mypayment.php 


// Include database connection
include("db.php");
include("function.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$login_email_user = $_SESSION['email']; // Get email from session
$user_id = $_SESSION['user_id'];

// Database connection using MySQLi
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit("Database connection failed.");
}

updateRevenueForUser($mysqli, $login_email_user);

// Fetch user financial data from msusers table
$sql_user = "
    SELECT current_revenue, local_revenue_paid, local_revenue_unpaid, 
           current_revenue_from_partner, partner_revenue_paid, partner_revenue_unpaid, total_current_revenue 
    FROM msusers 
    WHERE loginemail = ?
";
$stmt_user = $mysqli->prepare($sql_user);
$stmt_user->bind_param("s", $login_email_user);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user_data = $user_result->fetch_assoc();

// Fetch the last 10 payments from payment_local_pubs
$sql_local = "
    SELECT id, email_pubs, nominal, payment_description, payment_date 
    FROM payment_local_pubs 
    WHERE email_pubs = ? 
    ORDER BY payment_date DESC 
    LIMIT 10
";
$stmt_local = $mysqli->prepare($sql_local);
$stmt_local->bind_param("s", $login_email_user);
$stmt_local->execute();
$result_local = $stmt_local->get_result();

// Fetch the last 10 payments from payment_partner_pubs_sync
$sql_partner = "
    SELECT id, email_pubs, nominal, payment_description, payment_date 
    FROM payment_partner_pubs_sync 
    WHERE email_pubs = ? 
    ORDER BY payment_date DESC 
    LIMIT 10
";
$stmt_partner = $mysqli->prepare($sql_partner);
$stmt_partner->bind_param("s", $login_email_user);
$stmt_partner->execute();
$result_partner = $stmt_partner->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payments</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include("main_menu.php"); ?>
<?php include("include_publisher_menu.php"); ?>

<div class="container mt-5">
    <h2>My Payment Records</h2>
    <h3>Total Current Revenue: Rp <?php echo number_format($user_data['total_current_revenue'], 2); ?></h3>

    <!-- Display user financial details -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h4>Local Revenue Summary</h4>
            <ul class="list-group">
                <li class="list-group-item">Current Revenue: Rp <?php echo number_format($user_data['current_revenue'], 2); ?></li>
                <li class="list-group-item">Local Revenue Paid: Rp <?php echo number_format($user_data['local_revenue_paid'], 2); ?></li>
                <li class="list-group-item">Local Revenue Unpaid: Rp <?php echo number_format($user_data['local_revenue_unpaid'], 2); ?></li>
            </ul>
        </div>
        <div class="col-md-6">
            <h4>Partner Revenue Summary</h4>
            <ul class="list-group">
                <li class="list-group-item">Revenue from Partner: Rp <?php echo number_format($user_data['current_revenue_from_partner'], 2); ?></li>
                <li class="list-group-item">Partner Revenue Paid: Rp <?php echo number_format($user_data['partner_revenue_paid'], 2); ?></li>
                <li class="list-group-item">Partner Revenue Unpaid: Rp <?php echo number_format($user_data['partner_revenue_unpaid'], 2); ?></li>
            </ul>
        </div>
    </div>

    <h2>Last 20 Payments</h2>

    <!-- Local Payments -->
    <h4>Local Payments</h4>
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Nominal</th>
                <th>Description</th>
                <th>Payment Date</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result_local->num_rows > 0): ?>
            <?php while ($row = $result_local->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['email_pubs']); ?></td>
                    <td><?php echo number_format($row['nominal'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['payment_description']); ?></td>
                    <td><?php echo htmlspecialchars($row['payment_date']); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" class="text-center">No local payment records found.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Partner Payments -->
    <h4>Partner Payments</h4>
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Nominal</th>
                <th>Description</th>
                <th>Payment Date</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result_partner->num_rows > 0): ?>
            <?php while ($row = $result_partner->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['email_pubs']); ?></td>
                    <td><?php echo number_format($row['nominal'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['payment_description']); ?></td>
                    <td><?php echo htmlspecialchars($row['payment_date']); ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" class="text-center">No partner payment records found.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the statement and database connection
$stmt_user->close();
$stmt_local->close();
$stmt_partner->close();
$mysqli->close();
?>
