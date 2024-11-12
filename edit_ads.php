<?php
// edit_ads.php
include("db.php");
include("function.php");
session_start();

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID and provider domain URL
$this_providers_id = 1; // Replace with actual logic to get provider ID if needed
//$this_providers_domain_url = get_providers_domain_url($conn, $this_providers_id);

$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the data from the form
    $ad_id = $_POST['ad_id'];
    $title_ads = $_POST['title_ads'];
    $description_ads = $_POST['description_ads'];
    $landingpage_ads = $_POST['landingpage_ads'];
    $image_url = $_POST['image_url'];
    $budget_per_click_textads = $_POST['budget_per_click_textads'];
    $existing_image_url = $_POST['existing_image_url']; // Existing image URL from hidden input


    // Image upload handling
    if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image_url'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'png','jpeg','webp'];
        $max_file_size = 5 * 1024 * 1024; // 5MB

        // Validate file extension and size
        if (!in_array($file_ext, $allowed_ext)) {
            echo "<div class='alert alert-danger text-center'>Invalid file type. Only JPG and PNG files are allowed.</div>";
            exit();
        }
        if ($file['size'] > $max_file_size) {
            echo "<div class='alert alert-danger text-center'>File size must be less than 5MB.</div>";
            exit();
        }

        // Generate unique file name: id_random_word.extension
        $random_word = md5(uniqid(rand(), true)); // Generate a random word/hash
        $new_image_name = $ad_id . "_" . $random_word . "." . $file_ext;

        // Move uploaded file to the 'uploads' directory
        $upload_directory = 'banner_mini/';
        $new_image_path = $upload_directory . $new_image_name;
        $full_file_name_with_url = $this_providers_domain_url."/".$new_image_path;
        if (!move_uploaded_file($file['tmp_name'], $new_image_path)) {
            echo "<div class='alert alert-danger text-center'>Failed to upload image.</div>";
            exit();
        }

         // If the image is successfully uploaded, update the image URL in the database
        $image_url = $full_file_name_with_url; // Set the new image path

        
    } else {
        // No new image uploaded, keep the current image_url from the form
       $image_url = $existing_image_url;
    }


    // Validate the input data
    if (empty($title_ads) || empty($description_ads) || empty($landingpage_ads) || empty($image_url) || empty($budget_per_click_textads)) {
        echo "Semua kolom harus diisi.";
        exit();
    }

    // Ensure that budget per click is a positive integer
    if (!is_numeric($budget_per_click_textads) || $budget_per_click_textads <= 0) {
        echo "Budget per click harus berupa angka positif.";
        exit();
    }

    // Start a transaction to ensure both updates succeed together
    $conn->begin_transaction();

    try {
        // Prepare the update statement for the `advertisers_ads` table

          // Set timezone to GMT+7
        date_default_timezone_set('Asia/Jakarta'); // GMT +7


        $stmt_ads = $conn->prepare("
            UPDATE advertisers_ads 
            SET title_ads = ?, 
                description_ads = ?, 
                landingpage_ads = ?, 
                image_url = ?, 
                budget_per_click_textads = ?, 
                last_update = NOW() 

            WHERE id = ?");

        $stmt_ads->bind_param("ssssii", $title_ads, $description_ads, $landingpage_ads, $image_url, $budget_per_click_textads, $ad_id);

        // Execute the update query for `advertisers_ads`
        if (!$stmt_ads->execute()) {
            throw new Exception("Error updating advertisers_ads.");
        }

        // Prepare the update statement for the `mapping_advertisers_ads_publishers_site` table
        $stmt_mapping = $conn->prepare("
            UPDATE mapping_advertisers_ads_publishers_site 
            SET title_ads = ?, 
                description_ads = ?, 
                landingpage_ads = ?, 
                image_url = ?, 
                budget_per_click_textads = ? , 
                last_updated = now() 
            WHERE local_ads_id = ? AND ads_providers_domain_url = ?");

        $stmt_mapping->bind_param("ssssiis", $title_ads, $description_ads, $landingpage_ads, $image_url, $budget_per_click_textads, $ad_id, $this_providers_domain_url);

        // Execute the update query for `mapping_advertisers_ads_publishers_site`
        if (!$stmt_mapping->execute()) {
            throw new Exception("Error updating mapping_advertisers_ads_publishers_site.");
        }

        // Commit the transaction if both updates were successful
        $conn->commit();

        // Redirect back to view_ads.php after a successful update
        header("Location: view_ads.php");
        exit();
    } catch (Exception $e) {
        // Rollback the transaction in case of any errors
        $conn->rollback();
        echo "Terjadi kesalahan saat memperbarui iklan: " . $e->getMessage();
    }

    // Close the statements
    $stmt_ads->close();
    $stmt_mapping->close();
}

$conn->close();
?>
