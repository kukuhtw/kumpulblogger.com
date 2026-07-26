<?php
// add_media_influencer.php

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

// Fetch media options from `media` table
$media_options_query = "SELECT `id`, `media`, `desc` FROM media";
$media_result = $mysqli->query($media_options_query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $media_id = $_POST['media_id'];
//    $media_name = $_POST['media_name'];
    $media_url = $_POST['media_url'];
    $rate_owner = $_POST['rate_owner'];
    $owner_media_desc = $_POST['owner_media_desc'];
    $regdate = date('Y-m-d H:i:s');

     // Fetch media name from `media` table based on the selected media_id
    $media_name_query = "SELECT `media` FROM media WHERE `id` = ?";
    $stmt_media = $mysqli->prepare($media_name_query);
    $stmt_media->bind_param("i", $media_id);
    $stmt_media->execute();
    $stmt_media->bind_result($media_name);
    $stmt_media->fetch();
    $stmt_media->close();
    
    // Calculate rate_markup_provider and rate_partner
    $rate_markup_provider = $rate_owner / 6;
    $rate_partner = $rate_owner / 6;

    $harga_jual_lokal=$rate_owner + $rate_markup_provider;
    $harga_jual_partner=$rate_owner + $rate_markup_provider + $rate_partner;

// Round the prices to the nearest multiple of 50
$rate_markup_provider = round($rate_markup_provider / 50) * 50;
$rate_partner = round($rate_partner / 50) * 50;
         


    // Provider domain URL
    $owner_provider_domain_url = get_providers_domain_url_json("providers_data.json", 1);

    // Insert into `influencer_media` table
    $insert_query = "INSERT INTO influencer_media (owner_id, owner_provider_domain_url, media_id, media_name, media_url, owner_media_desc, rate_owner, rate_markup_provider, rate_partner, regdate)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $mysqli->prepare($insert_query);
    $stmt->bind_param("isisssddds", $user_id, $owner_provider_domain_url, $media_id, $media_name, $media_url, $owner_media_desc, $rate_owner, $rate_markup_provider, $rate_partner, $regdate);
    
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Media added successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Add Influencer Media</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="card-body">
                 <?php include("main_menu.php") ?>
                <?php include("include_publisher_menu.php") ?>
                 <p>&nbsp;</p>

                <h2 class="text-center">Add Influencer Media</h2>
                <form action="add_media_influencer.php" method="POST">
                    <div class="mb-3">
                        <label for="media_id" class="form-label">Select Media</label>
                        <select class="form-control" id="media_id" name="media_id" required>
                            <?php while ($row = $media_result->fetch_assoc()): ?>
                <option value="<?= $row['id']; ?>"><?= $row['media']; ?> - <?= $row['desc']; ?></option>
            <?php endwhile; ?>
                        </select>
                    </div>
                   <div class="mb-3">
    <label for="media_url" class="form-label">Media URL</label>
    <input type="url" class="form-control" id="media_url" name="media_url" value="https://" placeholder="https://" required>
</div>
                    <div class="mb-3">
                        <label for="rate_owner" class="form-label">Rate Owner (RP 5,000 - 5,000,000)</label>
                        <input type="number" class="form-control" id="rate_owner" name="rate_owner" min="5000" max="5000000" required>
                    </div>
                    <div class="mb-3">
                        <label for="owner_media_desc" class="form-label">Media Description (Followers/Subscribers, etc.)</label>
                        <textarea class="form-control" id="owner_media_desc" name="owner_media_desc" rows="3" required></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-block">Add Media</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
