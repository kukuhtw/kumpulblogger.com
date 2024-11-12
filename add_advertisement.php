<?php
// add_advertisement.php  Include database connection
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


// Get provider's domain URL
$this_providers_id = 1;
//$this_providers_domain_url = get_providers_domain_url($mysqli, $this_providers_id);

$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);

//$this_providers_name =  get_providers_name($mysqli, $this_providers_id) ;


$this_providers_name = getProvidersNameById_JSON("providers_data.json", 1);


// Function to check the number of ads submitted in the last 1 hour
function check_submission_limit($mysqli, $user_id) {
    $sql = "SELECT COUNT(*) as submission_count FROM advertisers_ads WHERE advertisers_id = ? AND regdate >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['submission_count'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check submission limit (max 5 entries per hour)
    if (check_submission_limit($mysqli, $user_id) >= 5) {
        echo "<div class='alert alert-danger text-center'>You have reached the maximum submission limit. Please wait for 1 hour before submitting again.</div>";
        exit();
    }

    // Retrieve and sanitize input fields
    $title_ads = $mysqli->real_escape_string($_POST['title_ads']);
    $description_ads = $mysqli->real_escape_string($_POST['description_ads']);
    $landingpage_ads = $mysqli->real_escape_string($_POST['landingpage_ads']);
    $budget_per_click_textads = (int)$_POST['budget_per_click_textads'];
    $budget_allocation = (int)$_POST['budget_allocation'];
    $regdate = date('Y-m-d H:i:s', strtotime('+7 hours')); // GMT +7 time

    // Validate budget_per_click_textads range
    if ($budget_per_click_textads < 30 || $budget_per_click_textads > 3000) {
        echo "<div class='alert alert-danger text-center'>Budget per click must be between Rp 30 and Rp 3000.</div>";
        exit();
    }

    // Validate budget_allocation range
    if ($budget_allocation < 5000 || $budget_allocation > 60000000) {
        echo "<div class='alert alert-danger text-center'>Budget allocation must be between Rp 5,000 and Rp 60,000,000.</div>";
        exit();
    }

    // Handle image upload
    if (isset($_FILES['image_url']) && $_FILES['image_url']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image_url'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'png'];
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

        // Generate unique file name with ID and MD5 hash
        $last_id_query = "SELECT id FROM advertisers_ads ORDER BY id DESC LIMIT 1";
        $last_id_result = $mysqli->query($last_id_query);
        $last_id_row = $last_id_result->fetch_assoc();
        $new_id = $last_id_row ? $last_id_row['id'] + 1 : 1;
        $file_name = $new_id . '_' . md5(time()) . '.' . $file_ext;
        $file_path = 'banner_mini/' . $file_name;
        $full_file_name_with_url = $this_providers_domain_url."/".$file_path;

        // Move the uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            echo "<div class='alert alert-danger text-center'>Failed to upload the image.</div>";
            exit();
        }
    } else {
        echo "<div class='alert alert-danger text-center'>Image upload is required.</div>";
        exit();
    }

    // Insert the ad into the database
   

   // Insert the ad into the database without setting local_ads_id initially
$sql = "INSERT INTO advertisers_ads 
        (providers_name, providers_domain_url, advertisers_id, title_ads, description_ads, landingpage_ads, image_url, regdate, budget_per_click_textads, budget_allocation, last_update) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ssisssssiis', $this_providers_name, $this_providers_domain_url, $user_id, $title_ads, $description_ads, $landingpage_ads, $full_file_name_with_url, $regdate, $budget_per_click_textads, $budget_allocation, $regdate);

if ($stmt->execute()) {
    // Get the auto-incremented ID (id field) of the newly inserted row
    $inserted_id = $mysqli->insert_id;

    // Update local_ads_id to be the same as the auto-incremented ID
    $update_sql = "UPDATE advertisers_ads SET local_ads_id = ? WHERE id = ?";
    $update_stmt = $mysqli->prepare($update_sql);
    $update_stmt->bind_param('ii', $inserted_id, $inserted_id);

    if ($update_stmt->execute()) {
        echo "<div class='alert alert-success text-center'>Advertisement added successfully!</div>";
        header("Location: view_ads.php");
    } else {
        echo "<div class='alert alert-danger text-center'>Error updating local_ads_id: " . $update_stmt->error . "</div>";
    }
} else {
    echo "<div class='alert alert-danger text-center'>Error: " . $stmt->error . "</div>";
}


}

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Submit Advertisement</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            margin-top: 50px;
            max-width: 900px;
        }

        .card {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .form-label {
            font-weight: 500;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .alert {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="card-body">
               
                 <?php include("main_menu.php") ?>
        <?php include("include_advertiser_menu.php") ?>
 <p>&nbsp;</p>
                <h2 class="text-center">Submit Your Advertisement</h2>
                

   <form method="POST" enctype="multipart/form-data">
    <!-- Information Alert -->
    <div class="alert alert-info" role="alert">
        <span class="text-success">Semua informasi dapat diedit setelah disimpan</span>, kecuali kolom <span class="text-danger"><strong>Total Budget Allocation</strong></span>.
    </div>

    <div class="mb-3">
        <label for="title_ads" class="form-label text-success">Title: (Dapat diedit kemudian)</label>
        <input type="text" name="title_ads" id="title_ads" class="form-control" placeholder="Enter ad title" required>
    </div>
    
    <div class="mb-3">
        <label for="description_ads" class="form-label text-success">Description (Dapat diedit kemudian, maksimal 250 charcters.)</label>
        <textarea name="description_ads" id="description_ads" class="form-control" rows="4" placeholder="Enter ad description" required></textarea>
    </div>

    <div class="mb-3">
        <label for="landingpage_ads" class="form-label text-success">Landing Page URL (Dapat diedit kemudian)</label>
        <input type="url" name="landingpage_ads" id="landingpage_ads" class="form-control" placeholder="Enter landing page URL" required>
    </div>

    <div class="mb-3">
        <label for="image_url" class="form-label text-success">Upload Image (JPG, PNG, max 5MB) (Dapat diedit kemudian)</label>
        <input type="file" name="image_url" id="image_url" class="form-control" accept=".jpg, .jpeg, .png" required>
    </div>

    <div class="mb-3">
        <label for="budget_per_click_textads" class="form-label text-success">Budget per Click (Rp 30 - Rp 3000) (Dapat diedit kemudian)</label>
        <input type="number" name="budget_per_click_textads" id="budget_per_click_textads" class="form-control" min="30" max="3000" placeholder="Enter budget per click" required>
    </div>

    <div class="mb-3">
        <label for="budget_allocation" class="form-label text-danger">Total Budget Allocation (Rp 5,000 - Rp 60,000,000) (Tidak Dapat diedit)</label>
        <input type="number" name="budget_allocation" id="budget_allocation" class="form-control" min="5000" max="60000000" placeholder="Enter total budget allocation" required>
    </div>

    <div class="d-grid">
        <button type="submit" class="btn btn-primary btn-block">Submit Advertisement</button>
    </div>
</form>


            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
