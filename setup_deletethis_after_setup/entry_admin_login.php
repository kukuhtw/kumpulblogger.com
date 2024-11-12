<?php
/*
   Admin setup script - sets up the initial admin email and password.
   Insert a new admin with ID 1 if it doesn't exist, or update the existing one.
   Remember to delete this file after setup.
*/

// Start session
session_start();

$setup_error = '';
$setup_success = '';

// Process the form if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include("../db.php");

    // Database connection using MySQLi
    $conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        exit("Database connection failed.");
    }

    // Retrieve and sanitize admin email, password, and confirmation password
    $loginemail_admin = filter_var($_POST['loginemail'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($password !== $confirm_password) {
        $setup_error = "Passwords do not match.";
    } else {
        // Check if admin with ID 1 exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM `msadmin` WHERE `id` = 1");
        $stmt->execute();
        $stmt->bind_result($admin_exists);
        $stmt->fetch();
        $stmt->close();

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        if ($admin_exists) {
            // Update existing admin with ID 1
            $stmt = $conn->prepare("UPDATE `msadmin` SET `loginemail` = ?, `passwords` = ? WHERE `id` = 1");
            $stmt->bind_param("ss", $loginemail_admin, $hashed_password);
        } else {
            // Insert new admin with ID 1
            $stmt = $conn->prepare("INSERT INTO `msadmin` (`id`, `loginemail`, `passwords`) VALUES (1, ?, ?)");
            $stmt->bind_param("ss", $loginemail_admin, $hashed_password);
        }

        if ($stmt->execute()) {
            $setup_success = "Admin setup completed successfully. Remember to delete this file.";
        } else {
            $setup_error = "Failed to set up the admin.";
        }

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
    <title>Admin Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a1f36;
            color: #f8f9fa;
        }
        .container {
            max-width: 500px;
            margin-top: 50px;
            padding: 20px;
            background-color: #2c3e50;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .btn-primary {
            background-color: #2a9fd6;
            border-color: #2a9fd6;
        }
        .form-label {
            color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Admin Setup</h2>
        <?php if ($setup_success): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $setup_success; ?>
            </div>
        <?php endif; ?>
        <?php if ($setup_error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $setup_error; ?>
            </div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="mb-3">
                <label for="loginemail" class="form-label">Admin Email:</label>
                <input type="email" id="loginemail" name="loginemail" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Set Up Admin</button>
        </form>
        <p class="text-center text-warning mt-3">⚠️ Remember to delete this file after setting up the admin login.</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
