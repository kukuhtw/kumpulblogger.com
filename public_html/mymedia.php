<?php
// mymedia.php

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

// Create a connection to the MySQL database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check the connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Query to fetch media data where owner_id = $user_id
$media_query = "SELECT id, media_name, media_url, owner_media_desc, rate_owner, rate_markup_provider, rate_partner 
                FROM influencer_media 
                WHERE owner_id = ?";

$stmt = $mysqli->prepare($media_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($id, $media_name, $media_url, $owner_media_desc, $rate_owner, $rate_markup_provider, $rate_partner);

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
    <?php include("include_publisher_menu.php"); ?>

            <div class="card-body">
                <h2 class="text-center">My Media</h2>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Media Name</th>
                            <th>Media URL</th>
                            <th>Description</th>
                            <th>Owner Rate</th>
                            <th>Harga Jual Lokal Provider</th>
                            <th>Harga Jual Partner Provider</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($stmt->fetch()): 

$harga_jual_lokal=$rate_owner + $rate_markup_provider;
$harga_jual_partner=$rate_owner + $rate_markup_provider + $rate_partner;

// Round the prices to the nearest multiple of 50
$harga_jual_lokal = round($harga_jual_lokal / 50) * 50;
$harga_jual_partner = round($harga_jual_partner / 50) * 50;

                       
$owner_media_desc = str_replace("*","",$owner_media_desc);
$owner_media_desc = str_replace("#","",$owner_media_desc);

                            ?>
                        <tr>
                            <td><?= htmlspecialchars($media_name) ?></td>
                            <td><a href="<?= htmlspecialchars($media_url) ?>" target="_blank"><?= htmlspecialchars($media_url) ?></a></td>
                            <td><?= nl2br(htmlspecialchars($owner_media_desc)) ?></td>
                            <td>Rp <?= number_format($rate_owner, 0) ?></td>
                            <td>Rp <?= number_format($harga_jual_lokal, 0) ?></td>
                            <td>Rp <?= number_format($harga_jual_partner, 0) ?></td>
                            <td>
                                <a href="edit_media.php?id=<?= $id ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete_media.php?id=<?= $id ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this media?');">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
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
