<?php
// reg.php
include("db.php");
include("function.php");
include("function_send_email.php");
include("saatini.php");
include("settings_all.php");

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("<div class='alert alert-danger'>Database connection failed.</div>");
}

$send_email = 0;
$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);

function generateRandomPassword($length = 10) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if the reCAPTCHA token is set
    if (isset($_POST['recaptcha_response']) && !empty($_POST['recaptcha_response'])) {
        $recaptcha_response = $_POST['recaptcha_response'];

        $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
        $response = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
        $responseKeys = json_decode($response, true);

        if (!$responseKeys["success"] || $responseKeys["score"] < 0.5) {
            exit("<div class='alert alert-danger'>Verification failed. Please try again.</div>");
        }
    } else {
        exit("<div class='alert alert-danger'>Verification failed. Please try again.</div>");
    }

    // Sanitizing inputs
    $loginemail = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $whatsapp = filter_var(trim($_POST['whatsapp']), FILTER_SANITIZE_STRING);

    // Check if email already exists
    $check_email_stmt = $conn->prepare("SELECT COUNT(*) FROM msusers WHERE loginemail = ?");
    $check_email_stmt->bind_param("s", $loginemail);
    $check_email_stmt->execute();
    $check_email_stmt->bind_result($email_count);
    $check_email_stmt->fetch();
    $check_email_stmt->close();

    if ($email_count > 0) {
        echo "<div class='alert alert-danger'>Email sudah terdaftar. Silakan gunakan email lain atau <a href='login.php'>login</a> jika Anda sudah memiliki akun.</div>";
    } else {
        $passwords = generateRandomPassword(); // generate random password
        $hashed_password = password_hash($passwords, PASSWORD_DEFAULT); // hash password
        $forgot_password_key = ''; // can be used if needed
        $regdate = date('Y-m-d H:i:s');

        // Prepared statement to prevent SQL injection
        $stmt = $conn->prepare("INSERT INTO msusers (loginemail, passwords, whatsapp, forgot_password_key, regdate) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $loginemail, $hashed_password, $whatsapp, $forgot_password_key, $regdate);

        if ($stmt->execute()) {
            $send_email = 1;
            echo "<div class='alert alert-success'>Registrasi berhasil! Password Anda: " . htmlspecialchars($passwords) . ". <a href='login.php'>Lanjut ke login page</a></div>";
        } else {
            echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($stmt->error) . "</div>";
        }

        $stmt->close();
    }
}

if ($send_email == 1) {
    $from = "noreply@kumpulblogger.com";
    $to = $loginemail;
    $subject = "Pendaftaran " . htmlspecialchars($loginemail) . " di " . htmlspecialchars($this_providers_domain_url);
    $text = "Registrasi berhasil! " . htmlspecialchars($saatini) . ". Password Anda: " . htmlspecialchars($passwords) . " , berikut link login : " . htmlspecialchars($this_providers_domain_url) . "/login.php";

    sendmail($from, $to, $subject, $text, $SMTP_API_KEY, $DOMAIN_NAME);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Pengguna</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google reCAPTCHA v3 -->
    <script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars($recaptcha_site_key); ?>"></script>
    <script>
        grecaptcha.ready(function() {
            grecaptcha.execute('<?php echo htmlspecialchars($recaptcha_site_key); ?>', {action: 'submit'}).then(function(token) {
                document.getElementById('recaptchaResponse').value = token;
            });
        });
    </script>
</head>
<body>

    <div class="container mt-5">
        <h2 class="text-center mb-4">Form Registrasi <?php echo htmlspecialchars($this_providers_domain_url); ?></h2>
        <form action="" method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
                <div class="invalid-feedback">
                    Harap masukkan email yang valid.
                </div>
            </div>

            <div class="mb-3">
                <label for="whatsapp" class="form-label">Nomor WhatsApp</label>
                <input type="text" class="form-control" id="whatsapp" name="whatsapp" required>
                <div class="invalid-feedback">
                    Harap masukkan nomor WhatsApp Anda.
                </div>
            </div>

            <!-- Google reCAPTCHA -->
            <input type="hidden" name="recaptcha_response" id="recaptchaResponse">

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Daftar</button>
            </div>
        </form>
        <div class="mt-3">
            <a href="forgot_password.php">Lupa Password?</a> |
            <a href="login.php">Login</a> |
            <a href="index.php">Home</a>
        </div>
    </div>

    <!-- Bootstrap JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Script for Form Validation -->
    <script>
        // Example starter JavaScript for disabling form submissions if there are invalid fields
        (function () {
            'use strict'

            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.querySelectorAll('.needs-validation')

            // Loop over them and prevent submission
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
