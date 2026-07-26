<?php
/*
admin/change_code_provider.php
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

$change_code_error = '';
$change_code_success = '';

// Process the form if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include("../db.php");

    // Database connection using MySQLi
    $conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        exit("Database connection failed.");
    }

    // Get the new providers_code from the POST data
    $new_providers_code = $_POST['providers_code'];

    // Validate input
    if (empty($new_providers_code)) {
        $change_code_error = 'Provider code cannot be empty.';
    } else {
        // Update the providers_code in the database where id=1
        $sql = "UPDATE providers SET providers_code = ? WHERE id = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $new_providers_code);

        if ($stmt->execute()) {
            $change_code_success = 'Provider code `'.htmlspecialchars($new_providers_code).'` successfully updated.';
        } else {
            $change_code_error = 'Error updating provider code.';
        }

        // Close the statement and connection
        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Provider Code</title>
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
                        Change Provider Code
                    </div>
                    <div class="card-body">
                        <?php if (!empty($change_code_error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($change_code_error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($change_code_success)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo htmlspecialchars($change_code_success); ?>
                            </div>
                        <?php endif; ?>

                        <form action="change_code_provider.php" method="POST">
                            <div class="form-group">
                                <label for="providers_code">New Provider Code:</label>
                                <input type="text" class="form-control" id="providers_code" name="providers_code" required>
                            </div>
                            <button type="submit" class="btn btn-success btn-block">Update Provider Code</button>
                        </form>
                    </div>
                </div>
          
    </div>
</div>


<?php include("footer.php");?>

</body>
</html>
