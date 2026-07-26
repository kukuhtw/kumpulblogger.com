<?php

// pay_provider_partner.php <-- jangan dihilangkan

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
//$this_providers_domain_url = get_providers_domain_url($mysqli, $pid);

$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);

$payment_by  = $this_providers_domain_url;

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form input
    $email_provider_and_partner_provider = $_POST['email_provider_and_partner_provider'];

    list($email_provider, $partner_provider) = explode("~", $email_provider_and_partner_provider);
    $nominal = $_POST['nominal'];
    $payment_description = $_POST['payment_description'];
   $payment_date = date('Y-m-d H:i:s', strtotime($_POST['payment_date']));


    echo "<br>payment_date : ".$payment_date;

    // Prepare an SQL query to INSERT INTO `payment_partner_providers`
    $sql_inserts="INSERT INTO `payment_partner_providers` 
        (`partner_providers_domain_url`, `email_provider`, `nominal`, `payment_description`, `payment_date`, `payment_by`) 
        VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql_inserts);

    // Bind parameters
   // Bind parameters (Note: Use 'ssdsss' for correct data types)
    $stmt->bind_param('ssdsss', $partner_provider, $email_provider, $nominal, $payment_description, $payment_date, $payment_by);



    $p_v = "VALUES (
    '".$partner_provider."', 
    '".$email_provider."',
    '".$nominal."', 
    '".$payment_description."', 
    '".$payment_date."', 
    '".$payment_by."')";
    $sql_inserts_p=str_replace("VALUES (?, ?, ?, ?, ?, ?)", $p_v, $sql_inserts);

   //  echo "<br>sql_inserts_p : ".$sql_inserts_p;
    //  echo "<br>payment_date : ".$payment_date;

    // Execute the query
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Payment recorded successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }

    // Step 1: Sum all nominal values in `payment_partner_providers` where email_provider and partner_providers_domain_url match
    $query_sum_nominal = "
        SELECT SUM(nominal) AS total_paid 
        FROM payment_partner_providers 
        WHERE email_provider = ? 
        AND partner_providers_domain_url = ?
    ";

    $stmt = $mysqli->prepare($query_sum_nominal);
    $stmt->bind_param("ss", $email_provider, $partner_provider);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_paid = $row['total_paid'] ?: 0.00;  // Default to 0.00 if null
    $stmt->close();

    // Step 2: Update `providers_partners` table with the calculated `partner_revenue_paid` and `partner_revenue_unpaid`
    $query_update_revenue = "
        UPDATE providers_partners 
        SET 
            partner_revenue_paid = ?, 
            partner_revenue_unpaid = partner_revenue - ?, 
            last_updated_revenue = NOW() 
        WHERE providers_domain_url = ?
    ";

    $stmt = $mysqli->prepare($query_update_revenue);
    $stmt->bind_param("dds", $total_paid, $total_paid, $partner_provider);

    // Execute the update query
    if ($stmt->execute()) {
        echo "Revenue updated successfully!";
    } else {
        echo "Error updating revenue: " . $stmt->error;
    }

   

}

// Fetch all emails from the publisher_partner table
$sql = "SELECT email as 'email_provider', providers_domain_url FROM providers_contact_person_sync";
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
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">  
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
    <h2>Enter Payment Publisher Partner</h2>
    <form method="POST" action="">

        <div class="form-group mb-3">
            <label for="email_pubs">Select Email</label>
            <select name="email_provider_and_partner_provider" id="email_provider" class="form-control" required>
                <option value="">Select Email</option>
                <?php while ($row = $result->fetch_assoc()) : 
                    $email_provider = $row['email_provider'];
                     $providers_partners_domain_url = $row['providers_domain_url'];
                     $option_value=$email_provider."~".$providers_partners_domain_url;
                    ?>
            <option value="<?php echo $option_value; ?>">
                        <?php echo htmlspecialchars($row['email_provider']); ?> - <?php echo htmlspecialchars($row['providers_domain_url']); ?>
                    </option>
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

<div class="form-group mb-3">
    <label for="payment_date">Payment Date and Time</label>
    <input type="datetime-local" name="payment_date" id="payment_date" class="form-control" 
        value="<?php echo date('Y-m-d\TH:i'); ?>" required>
</div>

        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>

<?php
$mysqli->close();
include("footer.php");
?>

<?php include("js_toogle.php"); ?>

</body>
</html>
