<?php
// pay_pubs_local.php
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

$loginemail_admin = $_SESSION['loginemail_admin'];

// Database connection using MySQLi
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit("Database connection failed.");
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form input
    $email_pubs = $_POST['email_pubs'];
    $nominal = $_POST['nominal'];
    $payment_description = $_POST['payment_description'];
    $payment_date = date('Y-m-d H:i:s'); // Default is current timestamp

    // Prepare an SQL query to insert the payment data
    $stmt = $mysqli->prepare("INSERT INTO payment_local_pubs (email_pubs, nominal, payment_description, payment_date) VALUES (?, ?, ?, ?)");
    
    // Bind parameters
    $stmt->bind_param("sdss", $email_pubs, $nominal, $payment_description, $payment_date);
    
    // Execute the query
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Payment record successfully added.</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }

    // Close the statement
    $stmt->close();
}

// Fetch all emails from the msusers table
$sql = "SELECT loginemail FROM msusers";
$result = $mysqli->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Entry Form</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">  <?php include("style_toogle.php") ?>
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

    <h2>Enter Payment Publisher Local</h2>
    <form method="POST" action="">

        <div class="form-group mb-3">
            <label for="email_pubs">Select Email</label>
            <select name="email_pubs" id="email_pubs" class="form-control" required>
                <option value="">Select Email</option>
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <option value="<?php echo htmlspecialchars($row['loginemail']); ?>"><?php echo htmlspecialchars($row['loginemail']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group mb-3">
            <label for="nominal">Nominal (Amount)</label>
            <input type="number" name="nominal" id="nominal" class="form-control" step="0.01" required>
        </div>

        <div class="form-group mb-3">
            <label for="payment_description">Payment Description</label>
            <textarea name="payment_description" id="payment_description" class="form-control" rows="4" required></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>

<?php
// Close the database connection
$mysqli->close();
include("footer.php");

?>

<?php include("js_toogle.php"); ?>

</body>
</html>
