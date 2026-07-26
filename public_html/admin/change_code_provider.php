<?php
/*
admin/change_code_provider.php
*/
// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    // If not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

$loginemail_admin = $_SESSION['loginemail_admin'];

include("function_admin.php");
include("../db.php");

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

$change_code_error = '';
$change_code_success = '';

// Process the form if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the new providers_code from the POST data
    $new_providers_code = $_POST['providers_code'];

    // Validate input
    if (!admin_csrf_valid()) {
        $change_code_error = 'Permintaan tidak valid (CSRF token tidak cocok). Silakan muat ulang halaman dan coba lagi.';
    } elseif (empty($new_providers_code)) {
        $change_code_error = 'Provider code cannot be empty.';
    } else {
        // Update the providers_code in the database where id=1
        $sql = "UPDATE providers SET providers_code = ? WHERE id = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $new_providers_code);

        if ($stmt->execute()) {
            $change_code_success = 'Provider code `'.htmlspecialchars($new_providers_code).'` successfully updated.';
        } else {
            $change_code_error = 'Error updating provider code.';
        }

        $stmt->close();
    }
}

// Ambil identitas provider saat ini untuk ditampilkan (GET maupun setelah
// POST), supaya admin selalu lihat kode yang benar-benar aktif sekarang —
// sebelumnya halaman ini hanya menampilkan form kosong tanpa konteks apa pun.
$current_provider = null;
$provider_result = $conn->query("SELECT providers_name, providers_domain_url, providers_code FROM providers WHERE id = 1");
if ($provider_result) {
    $current_provider = $provider_result->fetch_assoc();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Provider Code</title>
    <?php include("style_toogle.php"); ?>

</head>

<body>

<div class="admin-navbar">
    <a class="brand" href="dashboard_admin.php">Admin Dashboard</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
</div>
<?php include("sidebar_menu.php");?>

<main class="admin-main" id="mainContent">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        Change Provider Code
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong>Fungsi halaman ini:</strong> mengatur <em>Provider Code</em> — kode undangan
                            jaringan Anda. Kode ini yang Anda bagikan ke calon partner ad network lain, lalu mereka
                            masukkan saat mengirim permintaan gabung federasi (lewat <code>join_force.php</code>
                            di sisi mereka / <code>API/request_join</code> di sisi kita). Permintaan gabung baru
                            hanya diterima kalau kode yang dikirim cocok dengan kode di sini.
                            <br><br>
                            <strong>Perlu diperhatikan:</strong> mengganti kode ini <u>tidak memutus</u> partner yang
                            sudah disetujui sebelumnya (mereka sudah punya <code>public_key</code>/<code>secret_key</code>
                            sendiri) — hanya memengaruhi permintaan gabung yang <em>belum</em> disetujui atau yang
                            baru akan masuk setelah ini. Kalau Anda sudah membagikan kode lama ke calon partner yang
                            belum sempat mengirim permintaan, beri tahu mereka kode barunya juga.
                        </div>

                        <?php if ($current_provider): ?>
                            <div class="mb-4">
                                <h5>Identitas Provider Saat Ini</h5>
                                <table class="table table-sm table-bordered mb-0">
                                    <tr>
                                        <th style="width:220px;">Nama Provider</th>
                                        <td><?php echo htmlspecialchars($current_provider['providers_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Domain</th>
                                        <td><?php echo htmlspecialchars($current_provider['providers_domain_url']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Provider Code Aktif</th>
                                        <td><code><?php echo htmlspecialchars($current_provider['providers_code']); ?></code></td>
                                    </tr>
                                </table>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($change_code_error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($change_code_error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($change_code_success)): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo htmlspecialchars($change_code_success); ?>
                            </div>
                        <?php endif; ?>

                        <form action="change_code_provider.php" method="POST">
                            <?php echo admin_csrf_field(); ?>
                            <div class="form-group">
                                <label for="providers_code">Kode Baru:</label>
                                <input type="text" class="form-control" id="providers_code" name="providers_code" required>
                            </div>
                            <button type="submit" class="btn btn-success btn-block">Update Provider Code</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
</main>

<?php include("js_toogle.php"); ?>

<?php include("footer.php");?>

</body>
</html>
