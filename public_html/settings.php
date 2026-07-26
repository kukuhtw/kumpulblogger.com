<?php
include("db.php");
session_start();


// Database connection
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}


// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize error and success messages
$error_message = '';
$success_message = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if new password is at least 8 characters
    if (strlen($new_password) < 8) {
        $error_message = "Password baru harus minimal 8 karakter.";
    } else if ($new_password !== $confirm_password) {
        $error_message = "Konfirmasi password baru tidak cocok.";
    } else {
        // Fetch the current password from the database
        $stmt = $conn->prepare("SELECT passwords FROM msusers WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Verify old password
        if (password_verify($old_password, $user['passwords'])) {
            // Hash the new password
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update the password in the database
            $update_stmt = $conn->prepare("UPDATE msusers SET passwords = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_new_password, $user_id);

            if ($update_stmt->execute()) {
                $success_message = "Password berhasil diubah!";
            } else {
                $error_message = "Terjadi kesalahan saat mengubah password.";
            }
            $update_stmt->close();
        } else {
            $error_message = "Password lama salah.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akun</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <?php include("main_menu.php") ?>
        <h1 class="text-center">Pengaturan Akun</h1>

        <!-- Display error or success message -->
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger text-center">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success text-center">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="old_password" class="form-label">Password Lama</label>
                <input type="password" class="form-control" id="old_password" name="old_password" required>
                <div class="invalid-feedback">Harap masukkan password lama Anda.</div>
            </div>

            <div class="mb-3">
                <label for="new_password" class="form-label">Password Baru</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
                <div class="invalid-feedback">Password baru harus minimal 8 karakter.</div>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                <div class="invalid-feedback">Harap konfirmasi password baru Anda.</div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Ganti Password</button>
            </div>
        </form>

        <div class="text-center mt-4">
            <a href="dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
        </div>
    </div>

    <!-- Bootstrap JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Form Validation Script -->
    <script>
        (function () {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');

            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>
