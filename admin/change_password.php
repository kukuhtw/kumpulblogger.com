<?php
/*
admin/change_password.php
*/
// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    // If not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

$loginemail_admin = $_SESSION['loginemail_admin'];

$change_password_error = '';
$change_password_success = '';

// Process the form if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include("../db.php");

    // Database connection using MySQLi
    $conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        exit("Database connection failed.");
    }

    // Get the admin's current email from the session
    $loginemail_admin = $_SESSION['loginemail_admin'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if the new passwords match
    if ($new_password !== $confirm_password) {
        $change_password_error = 'New password and confirm password do not match.';
    } else {
        // Get the current password hash from the database
        $sql = "SELECT passwords FROM msadmin WHERE loginemail = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $loginemail_admin);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        // Verify the current password
        if ($admin && password_verify($current_password, $admin['passwords'])) {
            // Hash the new password securely
            $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);

            // Update the password in the database
            $sql = "UPDATE msadmin SET passwords = ? WHERE loginemail = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $new_password_hash, $loginemail_admin);
            if ($stmt->execute()) {
                $change_password_success = 'Password successfully updated.';
            } else {
                $change_password_error = 'Error updating password.';
            }
        } else {
            $change_password_error = 'Current password is incorrect.';
        }
    }

    // Close the statement and connection if initialized
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
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
        .form-group label {
            font-weight: bold;
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
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
        .alert {
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="navbar">
    Admin Dashboard
    <a href="logout.php" style="float:right;">Logout</a>
</div>
<?php include("sidebar_menu.php");?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Change Password
                </div>
                <div class="card-body">
                    <!-- Display success or error messages -->
                    <?php if (!empty($change_password_error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($change_password_error); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($change_password_success)): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($change_password_success); ?>
                        </div>
                    <?php endif; ?>
                    <form action="change_password.php" method="POST">
                        <div class="form-group">
                            <label for="current_password">Current Password:</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password:</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password:</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                        </div>
                        <button type="submit" class="btn btn-success btn-block">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php");?>

</body>
</html>
