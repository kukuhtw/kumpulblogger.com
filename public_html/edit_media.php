<?php
// edit_media.php

include("db.php");
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

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$id = $_GET['id'];

// Fetch the existing media data
$media_query = "SELECT media_id, media_url, owner_media_desc, rate_owner FROM influencer_media WHERE id = ? AND owner_id = ?";
$stmt = $mysqli->prepare($media_query);
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$stmt->bind_result($media_id, $media_url, $owner_media_desc, $rate_owner);
$stmt->fetch();
$stmt->close();

// Fetch media options from `media` table
$media_options_query = "SELECT id, media FROM media";
$media_result = $mysqli->query($media_options_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $media_id = $_POST['media_id'];
    $media_url = $_POST['media_url'];
    $owner_media_desc = $_POST['owner_media_desc'];
    $rate_owner = $_POST['rate_owner'];

    // Update the media entry
    $update_query = "UPDATE influencer_media SET media_id = ?, media_url = ?, owner_media_desc = ?, rate_owner = ? WHERE id = ? AND owner_id = ?";
    $stmt = $mysqli->prepare($update_query);
    $stmt->bind_param("isssii", $media_id, $media_url, $owner_media_desc, $rate_owner, $id, $user_id);

    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Media updated successfully!</div>";
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
    <title>Edit Media</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="card mt-4">
              <?php include("main_menu.php"); ?>
              <?php include("include_publisher_menu.php"); ?>
    
            <div class="card-body">
                <h2 class="text-center">Edit Media</h2>
                <form method="POST">
                    <div class="mb-3">
                        <label for="media_id" class="form-label">Media Name</label>
                        <select class="form-control" id="media_id" name="media_id" required>
                            <?php while ($row = $media_result->fetch_assoc()): ?>
                                <option value="<?= $row['id']; ?>" <?= ($media_id == $row['id']) ? 'selected' : '' ?>><?= htmlspecialchars($row['media']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="media_url" class="form-label">Media URL</label>
                        <input type="url" class="form-control" id="media_url" name="media_url" value="<?= htmlspecialchars($media_url) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="owner_media_desc" class="form-label">Description</label>
                        <textarea class="form-control" id="owner_media_desc" name="owner_media_desc" rows="3" required><?= htmlspecialchars($owner_media_desc) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="rate_owner" class="form-label">Owner Rate</label>
                        <input type="number" class="form-control" id="rate_owner" name="rate_owner" value="<?= htmlspecialchars($rate_owner) ?>" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
