<?php

include("db.php");
include("function.php");

// Database connection
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}


$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);


if (isset($_GET['key'])) {
    $forgot_password_key = trim((string) $_GET['key']);

    // Check if the key is valid
    $key_stmt = $conn->prepare("SELECT id FROM msusers WHERE forgot_password_key = ? AND forgot_password_key <> '' LIMIT 1");
    $key_stmt->bind_param("s", $forgot_password_key);
    $key_stmt->execute();
    $user = $key_stmt->get_result()->fetch_assoc();
    $key_stmt->close();

    if ($user) {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            if (strlen($new_password) < 8) {
                echo "<div class='alert alert-danger'>Password minimal 8 karakter.</div>";
            } elseif ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $user_id = (int) $user['id'];

                // Bind both the owner ID and token so a token cannot be reused
                // or swapped between the validation and update queries.
                $update_stmt = $conn->prepare(
                    "UPDATE msusers SET passwords = ?, forgot_password_key = ''
                     WHERE id = ? AND forgot_password_key = ?"
                );
                $update_stmt->bind_param("sis", $hashed_password, $user_id, $forgot_password_key);
                if ($update_stmt->execute() && $update_stmt->affected_rows === 1) {
                    echo "<div class='alert alert-success'>Password berhasil diubah!</div>";
                } else {
                    error_log('Failed to reset password: ' . $update_stmt->error);
                    echo "<div class='alert alert-danger'>Link reset password tidak valid atau telah digunakan.</div>";
                }
                $update_stmt->close();
            } else {
                echo "<div class='alert alert-danger'>Password dan konfirmasi password tidak cocok.</div>";
            }
        }
    } else {
        echo "<div class='alert alert-danger'>Link reset password tidak valid atau telah kadaluarsa.</div>";
    }
} else {
    echo "<div class='alert alert-danger'>Key tidak ditemukan di URL.</div>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Reset Password Anda: <?php echo htmlspecialchars($this_providers_domain_url); ?></h2>
        <form action="" method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="new_password" class="form-label">Password Baru</label>
                <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required>
                <div class="invalid-feedback">
                    Harap masukkan password baru Anda.
                </div>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                <div class="invalid-feedback">
                    Harap konfirmasi password Anda.
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Ubah Password</button>
            </div>

            <div class="mt-3">
            <a href="login.php">Login</a> | 
            <a href="reg.php">Daftar</a> |
            
            </div>

        </form>
    </div>

    <!-- Bootstrap JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Script for Form Validation -->
    <script>
        (function () {
            'use strict'

            var for
