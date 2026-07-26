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
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Initialize variables for error and success messages
$error_message = '';
$success_message = '';


// Handle form submission to update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $realname = $_POST['realname'];
    $bank = $_POST['bank'];
    $account_name = $_POST['account_name'];
    $account_number = $_POST['account_number'];

    // Simple validation
    if (empty($realname) || empty($bank) || empty($account_name) || empty($account_number)) {
        $error_message = "Semua kolom harus diisi.";
    } else {
        // Update user data in the database
        $update_stmt = $conn->prepare("UPDATE msusers SET realname = ?, bank = ?, account_name = ?, account_number = ? WHERE id = ?");
        $update_stmt->bind_param("ssssi", $realname, $bank, $account_name, $account_number, $user_id);

        if ($update_stmt->execute()) {
            $success_message = "Profil berhasil diperbarui!";
        } else {
            $error_message = "Terjadi kesalahan saat memperbarui profil.";
        }

        $update_stmt->close();
    }
}


// Fetch user data from the database
$stmt = $conn->prepare("SELECT realname, bank, account_name, account_number FROM msusers WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <?php include("main_menu.php") ?>
        <h1 class="text-center">Profil Pengguna</h1>
        
        <!-- Display success or error messages -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success text-center">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger text-center">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="realname" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="realname" name="realname" value="<?php echo htmlspecialchars($user['realname']); ?>" required>
                <div class="invalid-feedback">Harap masukkan nama lengkap Anda.</div>
            </div>

            <div class="mb-3">
                <label for="bank" class="form-label">Nama Bank</label>
                <select class="form-select" name="bank" required>
                    <option value="BCA" <?php if ($user['bank'] == 'BCA') echo 'selected'; ?>>BCA</option>
                    <option value="Mandiri" <?php if ($user['bank'] == 'Mandiri') echo 'selected'; ?>>Mandiri</option>
                    <option value="CIMB Niaga" <?php if ($user['bank'] == 'CIMB Niaga') echo 'selected'; ?>>CIMB Niaga</option>
                    <option value="BNI" <?php if ($user['bank'] == 'BNI') echo 'selected'; ?>>BNI</option>
                    <option value="BRI" <?php if ($user['bank'] == 'BRI') echo 'selected'; ?>>BRI</option>
                    <option value="BSI" <?php if ($user['bank'] == 'BSI') echo 'selected'; ?>>BSI</option>
                    <option value="Wallet go-pay" <?php if ($user['bank'] == 'Wallet go-pay') echo 'selected'; ?>>Wallet go-pay</option>
                    <option value="Wallet dana" <?php if ($user['bank'] == 'Wallet dana') echo 'selected'; ?>>Wallet dana</option>
                    <option value="Wallet Ovo" <?php if ($user['bank'] == 'Wallet Ovo') echo 'selected'; ?>>Wallet Ovo</option>
                </select>
                <div class="invalid-feedback">Harap pilih bank Anda.</div>
            </div>

            <div class="mb-3">
                <label for="account_name" class="form-label">Nama Pemilik Rekening</label>
                <input type="text" class="form-control" id="account_name" name="account_name" value="<?php echo htmlspecialchars($user['account_name']); ?>" required>
                <div class="invalid-feedback">Harap masukkan nama pemilik rekening.</div>
            </div>

            <div class="mb-3">
                <label for="account_number" class="form-label">Nomor Rekening</label>
                <input type="text" class="form-control" id="account_number" name="account_number" value="<?php echo htmlspecialchars($user['account_number']); ?>" required>
                <div class="invalid-feedback">Harap masukkan nomor rekening Anda.</div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>

    </div>

    <!-- Bootstrap JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>

