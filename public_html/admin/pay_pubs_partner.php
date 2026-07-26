<?php
// pay_pubs_partner.php

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

// Database connection using MySQLi
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit("Database connection failed.");
}

$pid = 1;
$this_providers_domain_url = get_providers_domain_url_json("../providers_data.json", 1);
$payment_by  = $this_providers_domain_url;

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form input
    $email_pubs = $_POST['email_pubs'];
    $nominal = $_POST['nominal'];
    $payment_description = $_POST['payment_description'];
    $payment_date = date('Y-m-d H:i:s'); // Default is current timestamp

    // Retrieve publisher_local_id and pubs_providers_domain_url based on email
    $stmt = $mysqli->prepare("SELECT publishers_local_id, pubs_providers_domain_url FROM publisher_partner WHERE loginemail = ?");
    $stmt->bind_param("s", $email_pubs);
    $stmt->execute();
    $stmt->bind_result($publisher_local_id, $pubs_providers_domain_url);
    $stmt->fetch();
    $stmt->close();

    // Insert payment data
    $stmt = $mysqli->prepare("INSERT INTO payment_partner_pubs (publisher_local_id, pubs_providers_domain_url, email_pubs, nominal, payment_description, payment_date, payment_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdsss", $publisher_local_id, $pubs_providers_domain_url, $email_pubs, $nominal, $payment_description, $payment_date, $payment_by);
    
    if ($stmt->execute()) {
        updateRevenueTotal($mysqli, $publisher_local_id, $pubs_providers_domain_url);
        updatePublisherRevenuePaid_unPaid($mysqli, $publisher_local_id, $pubs_providers_domain_url, $email_pubs);
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }

    $stmt->close();
}

// Fetch all emails from the publisher_partner table
$sql = "SELECT loginemail FROM publisher_partner";
$result = $mysqli->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Entry Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include("style_toogle.php") ?>
    <style>
        body { background-color: #f8f9fa; min-height: 100vh; }
        .container { margin-left: 250px; padding: 20px; }
    </style>
</head>
<body>

<?php include("sidebar_menu.php"); ?>
<div class="container" id="mainContent"> 
    <h2>Enter Payment Publisher Partner</h2>
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

        <!-- Bank details -->
        <div id="bank_details" style="display:none;">
            <div class="form-group mb-3">
                <label for="bank">Bank</label>
                <input type="text" id="bank" class="form-control" readonly>
            </div>

            <div class="form-group mb-3">
                <label for="account_name">Account Name</label>
                <input type="text" id="account_name" class="form-control" readonly>
            </div>

            <div class="form-group mb-3">
                <label for="account_number">Account Number</label>
                <input type="text" id="account_number" class="form-control" readonly>
            </div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('email_pubs').addEventListener('change', function() {
    var email = this.value;
    if (email) {
        // Make an AJAX request to fetch bank details
        fetch('fetch_bank_details.php?email=' + email)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('bank_details').style.display = 'block';
                    document.getElementById('bank').value = data.bank;
                    document.getElementById('account_name').value = data.account_name;
                    document.getElementById('account_number').value = data.account_number;
                } else {
                    document.getElementById('bank_details').style.display = 'none';
                }
            });
    } else {
        document.getElementById('bank_details').style.display = 'none';
    }
});
</script>

<?php
// Close the database connection
$mysqli->close();
include("footer.php");
include("js_toogle.php");
?>

</body>
</html>
