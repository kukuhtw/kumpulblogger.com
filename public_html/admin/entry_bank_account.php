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


$sync_message = '';
// Read the sync message now and render it later inside the page layout.
if (isset($_SESSION['sync_message'])) {
    $sync_message = $_SESSION['sync_message'];
    unset($_SESSION['sync_message']);
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
    if (!admin_csrf_valid()) {
        exit("Permintaan tidak valid (CSRF token tidak cocok). Silakan muat ulang halaman dan coba lagi.");
    }

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
    <?php include("style_toogle.php"); ?>
</head>
<body>

<div class="admin-navbar">
    <a class="brand" href="dashboard_admin.php">Admin Dashboard</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
</div>

<?php include("sidebar_menu.php"); ?>

<main class="admin-main" id="mainContent">
    <div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="mb-4">
            <h1 class="page-title">Provider Bank Account</h1>
            <p class="page-subtitle">Kelola rekening penerima pembayaran dan sinkronkan datanya ke provider partner.</p>
        </div>

        <?php if ($sync_message !== ''): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($sync_message); ?></div>
        <?php endif; ?>

        <div class="card">
        <div class="card-header">Account Details</div>
        <div class="card-body">
            <!-- HTML Form -->
            <form method="POST" action="">
                <?php echo admin_csrf_field(); ?>
                <div class="mb-3">
                    <label for="whatsapp" class="form-label">WhatsApp</label>
                    <input type="text" class="form-control" id="whatsapp" name="whatsapp" value="<?php echo htmlspecialchars($whatsapp); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="account_name" class="form-label">Account Name</label>
                    <input type="text" class="form-control" id="account_name" name="account_name" value="<?php echo htmlspecialchars($account_name); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="account_bank" class="form-label">Account Bank</label>
                    <select class="form-control" id="account_bank" name="account_bank" required>
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
                    <input type="text" class="form-control" id="account_number" name="account_number" value="<?php echo htmlspecialchars($account_number); ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">Save Account Details</button>
            </form>

            <hr class="my-4">
            <form method="POST" action="sync_databank.php" class="mb-0">
                <?php echo admin_csrf_field(); ?>
                <button type="submit" class="btn btn-outline-secondary">Sync to Provider Partner</button>
            </form>
        </div>
    </div>
    </div>
    </div>
</main>

<?php
// Close the database connection
$mysqli->close();
?>

<?php include("js_toogle.php"); ?>

<?php include("footer.php"); ?>

</body>
</html>
