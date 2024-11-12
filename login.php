<?php
// login.php
include("db.php");
include("function.php");
session_start(); // Start session to store user information

// Database connection
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
     $password = trim($password);



    // Prepared statement to check if the user exists
    $stmt = $conn->prepare("SELECT * FROM msusers WHERE loginemail = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $row['passwords'])) {
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['email'] = $row['loginemail'];

            // Update last_login and reset number_last_login_attempt to 0
            $last_login = date('Y-m-d H:i:s');
            $update_stmt = $conn->prepare("UPDATE msusers SET last_login = ?, number_last_login_attempt = 0 WHERE id = ?");
            $update_stmt->bind_param("si", $last_login, $row['id']);
            $update_stmt->execute();
            $update_stmt->close();

            // Redirect to a dashboard or home page
            header("Location: dashboard.php");
            exit();
        } else {
            // If login failed, update last_login_attempt and increment number_last_login_attempt
            $last_login_attempt = date('Y-m-d H:i:s');
            $update_stmt = $conn->prepare("UPDATE msusers SET last_login_attempt = ?, number_last_login_attempt = number_last_login_attempt + 1 WHERE id = ?");
            $update_stmt->bind_param("si", $last_login_attempt, $row['id']);
            $update_stmt->execute();
            $update_stmt->close();

            $error_message = "Password salah.";
        }
    } else {
        $error_message = "Email tidak ditemukan.";
    }

    $stmt->close();
    
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Login <?php echo htmlspecialchars($this_providers_domain_url); ?></h2>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        <form action="" method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
                <div class="invalid-feedback">
                    Harap masukkan email yang valid.
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">Show</button>
                </div>
                <div class="invalid-feedback">
                    Harap masukkan password Anda.
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
            
            <div class="mt-3">
                <a href="reg.php">Daftar</a> | 
                <a href="forgot_password.php">Lupa Password?</a>  | 
                <a href="index.php">Home</a> | 
            </div>
        </form>
    </div>

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

            // Toggle password visibility
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#password');

            togglePassword.addEventListener('click', function () {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.textContent = type === 'password' ? 'Show' : 'Hide';
            });
        })()
    </script>
</body>
</html>
