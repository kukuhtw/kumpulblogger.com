<?php
// list_invoice_payment.php

// Include database connection
include("db.php");
include("function.php");
include("settings_all.php");

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Create a connection to the MySQL database
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if (isset($_GET['message'])) { 
    ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_GET['message']); ?>
    </div>
<?php

} 


$this_providers_name = getProvidersNameById_JSON("providers_data.json", 1);



// Fetch distinct order_id and grand total for each order
$query = "
    SELECT DISTINCT order_id, SUM(total_price) AS grand_total
    FROM hasil_belanja_influencer 
    WHERE advertiser_id = ?


    GROUP BY order_id

     order by checkout_date desc
     
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>My Media</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="card mt-4">
            <?php include("main_menu.php"); ?>
             <br>
            <?php include("include_advertiser_menu.php"); ?>

        <h2 class="text-center">Order Payment Confirmation</h2>

        <table class="table table-bordered table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>Order ID</th>
                    <th>Media Details</th>
                    <th>Grand Total (Rp)</th>
                    <th>Confirm Payment</th>
                </tr>
            </thead>
            <tbody>
                <!-- PHP Loop Here -->
                <?php while ($row = $result->fetch_assoc()) { 
                    $order_id = $row['order_id'];
                    $grand_total = number_format($row['grand_total'], 0);

                    // Fetch media details for this order
                    $media_query = "
                        SELECT media_name, media_url, harga 
                        FROM hasil_belanja_influencer
                        WHERE order_id = ?
                    ";
                    $media_stmt = $conn->prepare($media_query);
                    $media_stmt->bind_param("s", $order_id);
                    $media_stmt->execute();
                    $media_result = $media_stmt->get_result();
                ?>
                <tr>
                    <td><?php echo $order_id; ?></td>
                    <td>
                        <?php while ($media_row = $media_result->fetch_assoc()) { ?>
                            <strong><?php echo $media_row['media_name']; ?></strong><br>
                            <a href="<?php echo $media_row['media_url']; ?>" target="_blank"><?php echo $media_row['media_url']; ?></a><br>
                            Price: Rp <?php echo number_format($media_row['harga'], 0); ?><br><br>
                        <?php } ?>
                    </td>
                    <td>Rp 

                        <?php echo $grand_total; 

$isipesan="halo admin ".$this_providers_name.", Saya sudah membayar untuk order_id: ".$order_id." , Sebesar Rp ".$grand_total . ", melalui bank xxxxx pada hari xxxx tanggal-bulan-tahun jam xxxx";

                    
$info_pembayaran = str_replace("{{ISIPESAN}}",urlencode($isipesan),$info_pembayaran);

                    echo "<p></p>".nl2br($info_pembayaran) ;


                ?></td>
                    <td>
                        <form class="payment-form" method="POST" action="confirm_payment.php">
                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                            <input type="hidden" name="grand_total" value="<?php echo $grand_total; ?>">
                            <div class="form-group">
                                <label>Bank Name</label>
                                <input type="text" class="form-control" name="bank_name" required>
                            </div>
                            <div class="form-group">
                                <label>Payment Date</label>
                                <input type="datetime-local" class="form-control" name="payment_date" required>
                            </div>
                            <div class="form-group">
                                <label>Sender Name</label>
                                <input type="text" class="form-control" name="sender_name" required>
                            </div>
                            <div class="form-group">
                                <label>Sender Bank</label>
                                <input type="text" class="form-control" name="sender_bank" required>
                            </div>
                            <button type="submit" class="btn btn-primary submit-btn">Confirm Payment</button>
                        </form>

                        <!-- Delete Button -->
            <form method="POST" action="delete_invoice.php" class="mt-2">
                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this order?');">Delete Invoice</button>
            </form>

                    </td>


                </tr>
                <?php 
                    $media_stmt->close();
                } ?>
                <!-- End PHP Loop -->
            </tbody>
        </table>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
