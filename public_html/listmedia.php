<?php
// listmedia.php

// Include database connection
include("db.php");
include("function.php");
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Initialize cart in session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle adding items to the cart
$cart_message = "";
if (isset($_POST['add_to_cart'])) {
    $owner_id = $_POST['owner_id'];
    $media_id = $_POST['media_id'];
    $media_name = $_POST['media_name'];
    $media_url = $_POST['media_url'];
    $harga_jual_lokal = $_POST['harga_jual_lokal'];

    // Check if the item already exists in the cart
    $item_exists = false;
    foreach ($_SESSION['cart'] as $cart_item) {
        if ($cart_item['owner_id'] == $owner_id && $cart_item['media_id'] == $media_id) {
            $item_exists = true;
            break;
        }
    }

    // If the item doesn't exist in the cart, add it
    if (!$item_exists) {
        $_SESSION['cart'][] = [
            'owner_id' => $owner_id,
            'media_id' => $media_id,
            'media_name' => $media_name,
            'media_url' => $media_url,
            'harga_jual_lokal' => $harga_jual_lokal,
            'quantity' => 1
        ];
        $cart_message = "<div class='alert alert-success'>Item added to cart successfully!</div>";
    } else {
        // Display notification if the item already exists
        $cart_message = "<div class='alert alert-warning'>This item is already in your cart.</div>";
    }
}

// Handle removing items from the cart
if (isset($_POST['remove_from_cart'])) {
    $remove_index = $_POST['remove_index'];
    if (isset($_SESSION['cart'][$remove_index])) {
        unset($_SESSION['cart'][$remove_index]);
        // Reindex the array to maintain correct indexes
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
}

// Handle clearing the entire cart
if (isset($_POST['clear_cart'])) {
    $_SESSION['cart'] = [];
}

// Handle checkout
if (isset($_POST['checkout'])) {
    $grand_total = 0;

    // Create a connection to the MySQL database
    $mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

    // Check the connection
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    // Generate a unique order ID in the format 'YYYY_MM_DD_HH_MM_SS_advertiserID'
    include("saatini.php");
    $order_id = date('Y_m_d_H_i_s') . '_' . $user_id;

    foreach ($_SESSION['cart'] as $cart_item) {
        $owner_id = $cart_item['owner_id'];
        $media_id = $cart_item['media_id'];
        $media_name = $cart_item['media_name'];
        $media_url = $cart_item['media_url'];
        $harga_jual_lokal = $cart_item['harga_jual_lokal'];
        $quantity = $cart_item['quantity'];
        $total_price = $harga_jual_lokal * $quantity;
        $grand_total += $total_price;

        // Insert into hasil_belanja_influencer table
        $checkout_date = date('Y-m-d H:i:s');
        $stmt = $mysqli->prepare("INSERT INTO hasil_belanja_influencer (order_id, advertiser_id, owner_id, media_id, media_name, media_url, harga, quantity, total_price, checkout_date) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siiissdiis", $order_id, $user_id, $owner_id, $media_id, $media_name, $media_url, $harga_jual_lokal, $quantity, $total_price, $checkout_date);
        $stmt->execute();
        $stmt->close();
    }

    // Clear the cart after checkout
    $_SESSION['cart'] = [];
    echo "<div class='alert alert-success'>Checkout successful! Grand Total: Rp " . number_format($grand_total, 0) . "</div>";
}

// Create a connection to the MySQL database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check the connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}


// Handle media type filter
$selected_media_type = isset($_POST['media_type']) ? $_POST['media_type'] : '';



// Query to fetch media data
$media_query = "SELECT id, owner_id, media_name, media_url, owner_media_desc, rate_owner, rate_markup_provider, rate_partner 
                FROM influencer_media ";

// If a media type is selected, filter the query
if (!empty($selected_media_type)) {
    $media_query .= " WHERE media_name = ?";
}

 $media_query .= " Order by regdate desc ";

$stmt = $mysqli->prepare($media_query);


// If a media type is selected, bind the parameter
if (!empty($selected_media_type)) {
    $stmt->bind_param("s", $selected_media_type);
}


$stmt->execute();
$stmt->bind_result($id, $owner_id, $media_name, $media_url, $owner_media_desc, $rate_owner, $rate_markup_provider, $rate_partner);

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


            <div class="card-body">
                <h2 class="text-center">My Media</h2>
                <?= $cart_message; // Display cart message here ?>


                 <!-- Media Filter Form -->
                <form method="POST" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <select name="media_type" class="form-control">
                                <option value="">All Media Types</option>
                                <option value="Blog" <?= ($selected_media_type == 'Blog') ? 'selected' : '' ?>>Blog</option>
                                <option value="Instagram" <?= ($selected_media_type == 'Instagram') ? 'selected' : '' ?>>Instagram</option>
                                <option value="Tiktok" <?= ($selected_media_type == 'Tiktok') ? 'selected' : '' ?>>Tiktok</option>
                                <option value="X.com" <?= ($selected_media_type == 'X.com') ? 'selected' : '' ?>>X.com</option>
                                <option value="Youtube" <?= ($selected_media_type == 'Youtube') ? 'selected' : '' ?>>Youtube</option>
                                <option value="Threads" <?= ($selected_media_type == 'Threads') ? 'selected' : '' ?>>Threads</option>
                                <option value="Facebook" <?= ($selected_media_type == 'Facebook') ? 'selected' : '' ?>>Facebook</option>
                                <option value="Linkedin" <?= ($selected_media_type == 'Linkedin') ? 'selected' : '' ?>>Linkedin</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </div>
                </form>


                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Media Name</th>
                            <th>Media URL</th>
                            <th>Description</th>
                           <th>Harga</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($stmt->fetch()): 
                            $harga_jual_lokal = round(($rate_owner + $rate_markup_provider) / 50) * 50;
                        
$owner_media_desc = str_replace("*","",$owner_media_desc);
$owner_media_desc = str_replace("#","",$owner_media_desc);


                        ?>
                        <tr>
                            <td><?= htmlspecialchars($media_name) ?></td>
                            <td><a href="<?= htmlspecialchars($media_url) ?>" target="_blank"><?= htmlspecialchars($media_url) ?></a></td>
                            <td><?= nl2br(htmlspecialchars($owner_media_desc)) ?></td>
                            <td>Rp <?= number_format($harga_jual_lokal, 0) ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="owner_id" value="<?= $owner_id ?>">
                                    <input type="hidden" name="media_id" value="<?= $id ?>">
                                    <input type="hidden" name="media_name" value="<?= htmlspecialchars($media_name) ?>">
                                    <input type="hidden" name="media_url" value="<?= htmlspecialchars($media_url) ?>">
                                    <input type="hidden" name="harga_jual_lokal" value="<?= $harga_jual_lokal ?>">
                                    <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm">Add to Cart</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Display Cart and Checkout -->
                <h3>Your Cart</h3>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Media Name</th>
                            <th>Media URL</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grand_total = 0;
                        foreach ($_SESSION['cart'] as $index => $cart_item): 
                            $total_price = $cart_item['harga_jual_lokal'] * $cart_item['quantity'];
                            $grand_total += $total_price;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($cart_item['media_name']) ?></td>
                            <td><?= htmlspecialchars($cart_item['media_url']) ?></td>
                            <td>Rp <?= number_format($cart_item['harga_jual_lokal'], 0) ?></td>
                            <td><?= $cart_item['quantity'] ?></td>
                            <td>Rp <?= number_format($total_price, 0) ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="remove_index" value="<?= $index ?>">
                                    <button type="submit" name="remove_from_cart" class="btn btn-danger btn-sm">Remove</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h4>Grand Total: Rp <?= number_format($grand_total, 0) ?></h4>

                <form method="POST">
                    <button type="submit" name="checkout" class="btn btn-success">Checkout</button>
                    <button type="submit" name="clear_cart" class="btn btn-warning">Clear Cart</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmt->close();
$mysqli->close();
?>
