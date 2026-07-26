<?php
// admin/entry_bank_account.php
session_start();
// Include the database connection
include("../db.php");
include("../function.php");
include("function_admin.php");

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    // If not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

$loginemail_admin = $_SESSION['loginemail_admin'];


// Display the sync message if it exists
if (isset($_SESSION['sync_message'])) {
    echo "<div class='alert alert-info'>" . htmlspecialchars($_SESSION['sync_message']) . "</div>";
    unset($_SESSION['sync_message']);  // Clear the message after displaying it
}


// Database connection using MySQLi
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit("Database connection failed.");
}

$id = 1;
//$this_providers_domain_url = get_providers_domain_url($mysqli, $id);

$this_providers_domain_url = get_providers_domain_url_json("../providers_data.json", 1);


// Retrieve data from the database
$whatsapp = '';
$account_name = '';
$account_bank = '';
$account_number = '';

$stmt = $mysqli->prepare("SELECT whatsapp, account_name, account_bank, account_number FROM providers_contact_person WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($whatsapp, $account_name, $account_bank, $account_number);
$stmt->fetch();
$stmt->close();

// Function to insert or update data in `providers_contact_person`
function insertOrUpdateContactPerson($mysqli, $id, $providers_domain_url, $whatsapp, $account_name, $account_bank, $account_number,$loginemail_admin) {
    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM providers_contact_person WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    $current_date = date('Y-m-d H:i:s');

    if ($count > 0) {
        // Update the record
        $update_stmt = $mysqli->prepare("
            UPDATE providers_contact_person 
            SET providers_domain_url = ?, whatsapp = ?, account_name = ?, account_bank = ?, account_number = ?, last_update = ? ,
                email = ? 
            WHERE id = ?");
        $update_stmt->bind_param("sssssssi", $providers_domain_url, $whatsapp, $account_name, $account_bank, $account_number, $current_date, $loginemail_admin, $id);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Insert new record
        $insert_stmt = $mysqli->prepare("
            INSERT INTO providers_contact_person 
            (id, providers_domain_url, whatsapp, account_name, account_bank, account_number, last_update, email) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("isssssss", $id, $providers_domain_url, $whatsapp, $account_name, $account_bank, $account_number, $current_date,$loginemail_admin);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form input
    $whatsapp = $_POST['whatsapp'];
    $account_name = $_POST['account_name'];
    $account_bank = $_POST['account_bank'];
    $account_number = $_POST['account_number'];

    // Validate account_bank selection
    $valid_banks = ['BCA', 'Mandiri', 'CIMB Niaga', 'BNI', 'BRI', 'BSI', 'Wallet go-pay', 'Wallet dana', 'Wallet Ovo'];
    if (!in_array($account_bank, $valid_banks)) {
        exit("Invalid bank selection.");
    }

    // Call the function to insert or update
    insertOrUpdateContactPerson($mysqli, $id, $this_providers_domain_url, $whatsapp, $account_name, $account_bank, $account_number,$loginemail_admin);

    // Redirect or show success message
    header("Location: entry_bank_account.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Account Bank Data Provider Adnetwork</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include("style_toogle.php") ?>
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
    <div class="card">
        <div class="card-header">Your Account Bank Data Provider Adnetwork</div>
        <div class="card-body">
            <!-- HTML Form -->
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="whatsapp" class="form-label">WhatsApp</label>
                    <input type="text" class="form-control" name="whatsapp" value="<?php echo htmlspecialchars($whatsapp); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="account_name" class="form-label">Account Name</label>
                    <input type="text" class="form-control" name="account_name" value="<?php echo htmlspecialchars($account_name); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="account_bank" class="form-label">Account Bank</label>
                    <select class="form-select" name="account_bank" required>
                        <option value="BCA" <?php if ($account_bank == 'BCA') echo 'selected'; ?>>BCA</option>
                        <option value="Mandiri" <?php if ($account_bank == 'Mandiri') echo 'selected'; ?>>Mandiri</option>
                        <option value="CIMB Niaga" <?php if ($account_bank == 'CIMB Niaga') echo 'selected'; ?>>CIMB Niaga</option>
                        <option value="BNI" <?php if ($account_bank == 'BNI') echo 'selected'; ?>>BNI</option>
                        <option value="BRI" <?php if ($account_bank == 'BRI') echo 'selected'; ?>>BRI</option>
                        <option value="BSI" <?php if ($account_bank == 'BSI') echo 'selected'; ?>>BSI</option>
                        <option value="Wallet go-pay" <?php if ($account_bank == 'Wallet go-pay') echo 'selected'; ?>>Wallet go-pay</option>
                        <option value="Wallet dana" <?php if ($account_bank == 'Wallet dana') echo 'selected'; ?>>Wallet dana</option>
                        <option value="Wallet Ovo" <?php if ($account_bank == 'Wallet Ovo') echo 'selected'; ?>>Wallet Ovo</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="account_number" class="form-label">Account Number</label>
                    <input type="text" class="form-control" name="account_number" value="<?php echo htmlspecialchars($account_number); ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">Submit</button>
            </form>

            <form method="POST" action="sync_databank.php">

            <button type="submit" class="btn btn-primary">Sync to Provider Partner</button>
            </form>


        </div>
    </div>
</div>

<?php
// Close the database connection
$mysqli->close();
include("footer.php");
?>

<?php include("js_toogle.php"); ?>

</body>
</html>
