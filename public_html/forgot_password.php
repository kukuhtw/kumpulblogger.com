<?php

include("db.php");
include("function.php");
include("function_send_email.php");
include("saatini.php");
include("settings_all.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';

// Database connection
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);

function generateForgotPasswordKey($length = 50) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

$send_email = 0;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $recaptcha_token = $_POST['recaptcha_token'];

    // Verify the reCAPTCHA response
    $recaptcha_secret = $recaptcha_secret; // Ensure this variable is correctly set in settings_all.php
    $verify_response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$recaptcha_token}");
    $response_data = json_decode($verify_response);

    $email = $_POST['email'];

    if ($response_data->success && $response_data->score >= 0.5) {
        // Check if the email exists
        $email = $conn->real_escape_string($email); // Escape email for security
        $sql = "SELECT * FROM msusers WHERE loginemail = '$email'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $forgot_password_key = generateForgotPasswordKey();
            $sql_update = "UPDATE msusers SET forgot_password_key='$forgot_password_key' WHERE loginemail='$email'";
            if ($conn->query($sql_update) === TRUE) {
                $send_email = 1;
                echo "<div class='alert alert-success'>Email untuk mengatur ulang password telah dikirim.</div>";
            } else {
                echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Email tidak ditemukan.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>reCAPTCHA validation failed. Please try again.</div>";
        exit;
    }
}

if ($send_email==1) {

    $from = "noreply@kumpulblogger.com";
    $to=$email;
    $subject = "Forgot password at ".$this_providers_domain_url;
    $text="Pada ".$saatini. ", anda meminta password baru, berikut link : ".$this_providers_domain_url."/reset_password.php?key=".$forgot_password_key;
   

    //echo "<br>text: ".$text;
    sendmail($from,$to,$subject,$text,$SMTP_API_KEY,$DOMAIN_NAME);

 
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- reCAPTCHA v3 -->
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo $recaptcha_site_key ?>"></script>
    <script>
        grecaptcha.ready(function() {
            grecaptcha.execute('<?php echo $recaptcha_site_key ?>', {action: 'forgot_password'}).then(function(token) {
                document.getElementById('recaptcha-token').value = token;
            });
        });
    </script>
</head>

<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Lupa Password <?php echo htmlspecialchars($this_providers_domain_url); ?></h2>
        <form action="" method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label">Masukkan Email Anda</label>
                <input type="email" class="form-control" id="email" name="email" required>
                <div class="invalid-feedback">
                    Harap masukkan email yang valid.
                </div>
            </div>

            <input type="hidden" name="recaptcha_token" id="recaptcha-token">

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Kirim Link Reset Password</button>
            </div>

            <div class="mt-3">
                <a href="reg.php">Daftar</a> |
                <a href="login.php">Login</a> |
                <a href="index.php">Home</a> |
            </div>
        </form>
    </div>

    <!-- Bootstrap JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Script for Form Validation -->
    <script>
        (function () {
            'use strict';

            var forms = document.querySelectorAll('.needs-validation');

            Array.prototype.slice.call(forms)
                .forEach(function (form) {
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
