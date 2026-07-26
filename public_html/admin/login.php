

<?php
/*
admin/login.php
*/

include("../db.php");

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

// Ambil data dari form
$loginemail_admin = $_POST['loginemail_admin'] ?? '';
$password = $_POST['password'] ?? '';

if ($loginemail_admin && $password) {
    // Cegah SQL Injection
    $loginemail_admin = $conn->real_escape_string($loginemail_admin);

    // Query untuk cek login
    $sql = "SELECT * FROM msadmin WHERE loginemail = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $loginemail_admin);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Pembatasan percobaan login gagal: kunci akun sementara setelah
        // beberapa kali gagal berturut-turut, memakai kolom yang sudah ada
        // di skema msadmin (number_last_login_attempt, last_login_attempt)
        // tapi sebelumnya tidak pernah dibaca/ditulis oleh halaman ini.
        $max_attempts = 5;
        $lockout_minutes = 15;
        $failed_attempts = (int) $row['number_last_login_attempt'];
        $is_locked = false;
        $seconds_remaining = 0;

        if ($failed_attempts >= $max_attempts && !empty($row['last_login_attempt'])) {
            $unlock_time = strtotime($row['last_login_attempt']) + ($lockout_minutes * 60);
            if ($unlock_time > time()) {
                $is_locked = true;
                $seconds_remaining = $unlock_time - time();
            }
        }

        if ($is_locked) {
            $minutes_remaining = (int) ceil($seconds_remaining / 60);
            echo "Login gagal: terlalu banyak percobaan gagal. Akun dikunci sementara, coba lagi dalam {$minutes_remaining} menit.";
        } elseif (password_verify($password, $row['passwords'])) {
            // Login berhasil
            session_start();
            $_SESSION['loggedin'] = true;
            $_SESSION['loginemail_admin'] = $loginemail_admin;

            // Update last login, reset hitungan percobaan gagal
            $sql = "UPDATE msadmin SET last_login = NOW(), number_last_login_attempt = 0 WHERE loginemail = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $loginemail_admin);
            $stmt->execute();

           // Redirect to the admin dashboard after successful login
            header("Location: dashboard_admin.php");
            exit;
        } else {
            // Login gagal: catat waktu percobaan & tambah hitungan gagal
            $sql = "UPDATE msadmin SET last_login_attempt = NOW(), number_last_login_attempt = number_last_login_attempt + 1 WHERE loginemail = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $loginemail_admin);
            $stmt->execute();

            $attempts_left = $max_attempts - ($failed_attempts + 1);
            if ($attempts_left <= 0) {
                echo "Login gagal: email atau password salah. Akun dikunci sementara selama {$lockout_minutes} menit karena terlalu banyak percobaan gagal.";
            } else {
                echo "Login gagal: email atau password salah. Sisa percobaan sebelum akun dikunci sementara: {$attempts_left}.";
            }
        }
    } else {
        echo "Login gagal: email tidak ditemukan";
    }

    $stmt->close();
} else {
    //echo "Harap isi semua kolom.";
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        h2 {
            text-align: center;
        }
        label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #218838;
        }
        .error {
            color: red;
            margin-bottom: 10px;
            text-align: center;
        }
        .show-password {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .show-password input {
            margin-left: 10px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Admin Login</h2>
    <form action="login.php" method="POST">
        <label for="loginemail">Email:</label>
        <input type="email" id="loginemail" name="loginemail_admin" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <div class="show-password">
            <input type="checkbox" id="togglePassword">
            <label for="togglePassword">Show Password</label>
        </div>

        <input type="submit" value="Login">
    </form>
</div>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');

    togglePassword.addEventListener('change', function () {
        // Toggle the type attribute between password and text
        if (password.type === 'password') {
            password.type = 'text';
        } else {
            password.type = 'password';
        }
    });
</script>

</body>
</html>
