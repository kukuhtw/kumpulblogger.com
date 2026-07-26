<?php

$setup_error = '';
$setup_success = '';

// Process the form if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    include("../db.php");

    // Database connection using MySQLi
    $conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        exit("Database connection failed.");
    }

    // Get form data
    $providers_name = $_POST['providers_name'];
    $providers_domain_url = $_POST['providers_domain_url'];

    // Generate random hash_key and secret_key
    $hash_key = bin2hex(random_bytes(16));
    $secret_key = bin2hex(random_bytes(32));

    // Set API endpoint
    $api_endpoint = $providers_domain_url . "/API";

    // Update data in providers table where id = 1
    $stmt = $conn->prepare("UPDATE providers SET providers_code = ?, providers_name = ?, providers_domain_url = ? , hash_key = ?, secret_key = ?, api_endpoint = ?, regdate = NOW() WHERE id = 1");
    $providers_code = strtoupper(substr(md5($providers_name), 0, 8)); // Generate a unique provider code
    $stmt->bind_param("ssssss", $providers_code, $providers_name, $providers_domain_url , $hash_key, $secret_key, $api_endpoint);

    if ($stmt->execute()) {
        // Success: Create providers_data.json in each folder
        $data = [
            "id" => 1,
            "providers_name" => $providers_name,
            "providers_domain_url" => $providers_domain_url
        ];

        $json_data = json_encode([$data], JSON_PRETTY_PRINT);

        $folders = ['../API', '../', '../cronjob', '../JSON', '../admin'];
        foreach ($folders as $folder) {
            file_put_contents($folder . '/providers_data.json', $json_data);
        }

        $setup_success = "Provider updated and JSON files generated successfully.";
    } else {
        $setup_error = "Failed to update provider: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a1f36;
            color: #f8f9fa;
        }
        .container {
            max-width: 500px;
            margin-top: 50px;
            padding: 20px;
            background-color: #2c3e50;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .btn-primary {
            background-color: #2a9fd6;
            border-color: #2a9fd6;
        }
        .form-label {
            color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Admin Setup</h2>
        <?php if ($setup_error): ?>
            <div class="alert alert-danger"><?= $setup_error ?></div>
        <?php elseif ($setup_success): ?>
            <div class="alert alert-success"><?= $setup_success ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="providers_name" class="form-label">Provider Name</label>
                <input type="text" class="form-control" id="providers_name" name="providers_name" required>
            </div>
            <div class="mb-3">
                <label for="providers_domain_url" class="form-label">Provider Domain URL</label>
                <input type="text" class="form-control" id="providers_domain_url" name="providers_domain_url" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Provider</button>
        </form>
    </div>
</body>
</html>
