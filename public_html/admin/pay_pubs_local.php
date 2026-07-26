<?php
// pay_pubs_local.php
session_start();
// Include the database connection
include("../db.php");
include("function_admin.php");

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    // If not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

$loginemail_admin = $_SESSION['loginemail_admin'];

// Database connection using MySQLi
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit("Database connection failed.");
}

// Serve email suggestions without loading every account into the page.
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['action']) && $_GET['action'] === 'suggest_email') {
    header('Content-Type: application/json; charset=utf-8');
    $term = trim($_GET['q'] ?? '');

    if (mb_strlen($term) < 2) {
        echo json_encode([]);
        exit;
    }

    $contains = '%' . $term . '%';
    $prefix = $term . '%';
    $stmt = $mysqli->prepare(
        "SELECT loginemail FROM msusers
         WHERE loginemail LIKE ?
         ORDER BY CASE WHEN loginemail LIKE ? THEN 0 ELSE 1 END, loginemail
         LIMIT 10"
    );
    $stmt->bind_param('ss', $contains, $prefix);
    $stmt->execute();
    $result = $stmt->get_result();
    $emails = [];
    while ($row = $result->fetch_assoc()) {
        $emails[] = $row['loginemail'];
    }
    $stmt->close();
    $mysqli->close();

    echo json_encode($emails);
    exit;
}

$payment_error = '';
$payment_success = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!admin_csrf_valid()) {
        exit("Permintaan tidak valid (CSRF token tidak cocok). Silakan muat ulang halaman dan coba lagi.");
    }

    // Get the form input
    $email_pubs = trim($_POST['email_pubs'] ?? '');
    $nominal = $_POST['nominal'];
    $payment_description = $_POST['payment_description'];
    $payment_date = date('Y-m-d H:i:s'); // Default is current timestamp

    // Only accept an existing account, even if the submitted input was edited manually.
    $email_stmt = $mysqli->prepare("SELECT 1 FROM msusers WHERE loginemail = ? LIMIT 1");
    $email_stmt->bind_param('s', $email_pubs);
    $email_stmt->execute();
    $email_exists = $email_stmt->get_result()->fetch_row() !== null;
    $email_stmt->close();

    if (!$email_exists) {
        $payment_error = 'Email publisher tidak ditemukan. Pilih salah satu email dari rekomendasi.';
    } else {
        $stmt = $mysqli->prepare("INSERT INTO payment_local_pubs (email_pubs, nominal, payment_description, payment_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdss", $email_pubs, $nominal, $payment_description, $payment_date);

        if ($stmt->execute()) {
            $payment_success = 'Payment record successfully added.';
        } else {
            $payment_error = 'Payment record gagal ditambahkan.';
        }
        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Entry Form</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">  <?php include("style_toogle.php") ?>
    <style>
        body {
            background-color: #f8f9fa;
            position: relative;
            min-height: 100vh;
        }
        .navbar {
            background-color: #343a40;
            color: white;
            padding: 10px;
            font-size: 18px;
            font-weight: bold;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }
        .sidebar {
            background-color: #343a40;
            padding: 20px;
            height: 100vh;
            position: fixed;
            color: white;
        }
        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }
        .sidebar ul li a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: white;
        }
        .sidebar ul li a:hover {
            background-color: #575757;
        }
        .container {
            margin-left: 250px;
            padding: 20px;
        }
        .card {
            margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #28a745;
            color: white;
            font-size: 24px;
            text-align: center;
        }
        .footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px;
            position: absolute;
            bottom: 0;
            width: 100%;
        }
        .table {
            margin-top: 20px;
        }
        .pagination {
            margin-top: 20px;
            justify-content: center;
        }
        .email-autocomplete { position: relative; }
        .email-suggestions {
            position: absolute;
            z-index: 1050;
            top: 100%;
            right: 0;
            left: 0;
            max-height: 260px;
            overflow-y: auto;
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
        }
        .email-suggestions:empty { display: none; }
    </style>
</head>
<body>

<?php include("sidebar_menu.php"); ?>

<div class="container" id="mainContent">    
    <div class="content">

    <h2>Enter Payment Publisher Local</h2>
    <?php if ($payment_error !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($payment_error); ?></div>
    <?php endif; ?>
    <?php if ($payment_success !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($payment_success); ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <?php echo admin_csrf_field(); ?>

        <div class="form-group mb-3 email-autocomplete">
            <label for="email_pubs">Email Publisher</label>
            <input type="email" name="email_pubs" id="email_pubs" class="form-control"
                   value="<?php echo htmlspecialchars($_POST['email_pubs'] ?? ''); ?>"
                   placeholder="Ketik minimal 2 karakter email" autocomplete="off"
                   aria-autocomplete="list" aria-controls="email_suggestions" required>
            <div id="email_suggestions" class="email-suggestions list-group" role="listbox"></div>
            <small id="email_help" class="form-text text-muted">Ketik beberapa karakter, lalu pilih email dari rekomendasi.</small>
        </div>

        <div class="form-group mb-3">
            <label for="nominal">Nominal (Amount)</label>
            <input type="number" name="nominal" id="nominal" class="form-control" step="0.01" required>
        </div>

        <div class="form-group mb-3">
            <label for="payment_description">Payment Description</label>
            <textarea name="payment_description" id="payment_description" class="form-control" rows="4" required></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>

<?php
// Close the database connection
$mysqli->close();
include("footer.php");

?>

<?php include("js_toogle.php"); ?>

<script>
(function () {
    const input = document.getElementById('email_pubs');
    const suggestions = document.getElementById('email_suggestions');
    const help = document.getElementById('email_help');
    let timer;
    let request;

    function clearSuggestions() {
        suggestions.replaceChildren();
        input.setAttribute('aria-expanded', 'false');
    }

    function selectEmail(email) {
        input.value = email;
        clearSuggestions();
        help.textContent = 'Email dipilih: ' + email;
        input.focus();
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        if (request) request.abort();
        const query = input.value.trim();

        if (query.length < 2) {
            clearSuggestions();
            help.textContent = 'Ketik minimal 2 karakter untuk melihat rekomendasi.';
            return;
        }

        help.textContent = 'Mencari email...';
        timer = setTimeout(function () {
            request = new AbortController();
            fetch('pay_pubs_local.php?action=suggest_email&q=' + encodeURIComponent(query), {
                signal: request.signal,
                headers: { 'Accept': 'application/json' }
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('Request failed');
                    return response.json();
                })
                .then(function (emails) {
                    clearSuggestions();
                    if (!emails.length) {
                        help.textContent = 'Tidak ada email yang cocok.';
                        return;
                    }

                    emails.forEach(function (email) {
                        const option = document.createElement('button');
                        option.type = 'button';
                        option.className = 'list-group-item list-group-item-action';
                        option.setAttribute('role', 'option');
                        option.textContent = email;
                        option.addEventListener('mousedown', function (event) {
                            event.preventDefault();
                            selectEmail(email);
                        });
                        suggestions.appendChild(option);
                    });
                    input.setAttribute('aria-expanded', 'true');
                    help.textContent = emails.length + ' email ditemukan. Pilih salah satu.';
                })
                .catch(function (error) {
                    if (error.name !== 'AbortError') {
                        clearSuggestions();
                        help.textContent = 'Rekomendasi email gagal dimuat. Silakan coba lagi.';
                    }
                });
        }, 250);
    });

    input.addEventListener('blur', function () {
        setTimeout(clearSuggestions, 150);
    });
})();
</script>

</body>
</html>
