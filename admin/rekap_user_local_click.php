<?php

// Start session
session_start();
// admin/rekap_user_local_click.php
include("../db.php"); // Koneksi database
include("../function.php");

// Create a connection to the MySQL database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check the connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$loginemail_admin = $_SESSION['loginemail_admin'];

// Ambil parameter GET user_id
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($user_id == 0) {
    die("User ID is required");
}

// Pagination
$limit = 10; // Rows per page
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Ambil data dari table `publishers_site` yang `publishers_local_id` sama dengan user_id
$query_publishers = "SELECT id FROM publishers_site WHERE publishers_local_id = ?";
$stmt_publishers = $mysqli->prepare($query_publishers);
$stmt_publishers->bind_param("i", $user_id);
$stmt_publishers->execute();
$result_publishers = $stmt_publishers->get_result();

// Ambil semua `pub_id` dari tabel `publishers_site`
$pub_ids = [];
while ($row = $result_publishers->fetch_assoc()) {
    $pub_ids[] = $row['id'];
}

// Close the statement
$stmt_publishers->close();

// Jika tidak ada publisher untuk user ini
if (empty($pub_ids)) {
    die("No publishers found for this user.");
}

// Ambil data dari `ad_clicks` berdasarkan `pub_id`
$query_clicks = "SELECT * FROM ad_clicks WHERE pub_id IN (" . implode(',', array_fill(0, count($pub_ids), '?')) . ") AND isaudit = 1 AND is_reject = 0 ORDER BY click_time DESC LIMIT ? OFFSET ?";
$stmt_clicks = $mysqli->prepare($query_clicks);

// Gabungkan `pub_ids`, `limit`, dan `offset` ke dalam satu array parameter
$types = str_repeat('i', count($pub_ids)) . 'ii';
$params = array_merge($pub_ids, [$limit, $offset]);

$stmt_clicks->bind_param($types, ...$params);
$stmt_clicks->execute();
$result_clicks = $stmt_clicks->get_result();

// Tampilkan data klik
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banned Browser List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include("style_toogle.php") ?>
    <style>
        /* Your CSS styles */
    </style>
</head>
<body>

<?php include("sidebar_menu.php"); ?>

<div class="container" id="mainContent">
    <h2 class="mb-4">Rekap Transaksi Klik User</h2>

    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
            <tr>
            <th scope="col">Click ID</th>
            <th scope="col">Publisher & Info</th>
            <th scope="col">Title Ads</th>
            <th scope="col">Landing Page</th>
            <th scope="col">Click Time</th>
            <th scope="col">Revenue Publishers</th>
        </tr>
        </thead>
        <tbody>
            <?php
             while ($row = $result_clicks->fetch_assoc()) {
            $publisher_info = "Publisher ID: {$row['pub_id']}<br>"
                            . "User Cookies: {$row['user_cookies']}<br>"
                            . "IP Address: {$row['ip_address']}<br>"
                            . "Browser Agent: {$row['browser_agent']}<br>"
                            . "Referrer: {$row['referrer']}<br>"
                            . "Site Name: {$row['site_name']}<br>"
                            . "Site Domain: {$row['site_domain']}";
            
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$publisher_info}</td>
                    <td>{$row['title_ads']}</td>
                    <td><a href='{$row['landingpage_ads']}' target='_blank'>Landing Page</a></td>
                    <td>{$row['click_time']} </td>
                    <td>{$row['revenue_publishers']}</td>
                </tr>";
        }
        ?>
    </tbody>
    </table>
<?php
// Close the click statement
$stmt_clicks->close();

// Hitung total revenue dari klik berdasarkan `pub_id`
$query_total_revenue = "SELECT SUM(revenue_publishers) AS total_revenue FROM ad_clicks WHERE pub_id IN (" . implode(',', array_fill(0, count($pub_ids), '?')) . ") AND isaudit = 1 AND is_reject = 0";
$stmt_total_revenue = $mysqli->prepare($query_total_revenue);
$stmt_total_revenue->bind_param(str_repeat('i', count($pub_ids)), ...$pub_ids);
$stmt_total_revenue->execute();
$result_total_revenue = $stmt_total_revenue->get_result();
$row_total_revenue = $result_total_revenue->fetch_assoc();

$total_revenue = $row_total_revenue['total_revenue'] ?: 0.00;
echo "<p>Total Revenue for this User: " . number_format($total_revenue, 2) . "</p>";

// Close the revenue statement
$stmt_total_revenue->close();

// Pagination
$query_total_rows = "SELECT COUNT(*) AS total_rows FROM ad_clicks WHERE pub_id IN (" . implode(',', array_fill(0, count($pub_ids), '?')) . ")";
$stmt_total_rows = $mysqli->prepare($query_total_rows);
$stmt_total_rows->bind_param(str_repeat('i', count($pub_ids)), ...$pub_ids);
$stmt_total_rows->execute();
$result_total_rows = $stmt_total_rows->get_result();
$row_total_rows = $result_total_rows->fetch_assoc();

$total_rows = $row_total_rows['total_rows'];
$total_pages = ceil($total_rows / $limit);
?>

    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php
            for ($i = 1; $i <= $total_pages; $i++) {
                $active = $i == $page ? 'active' : '';
                echo "<li class='page-item $active'><a class='page-link' href='rekap_user_local_click.php?user_id=$user_id&page=$i'>$i</a></li>";
            }
            ?>
        </ul>
    </nav>

</div>

<?php
include("js_toogle.php");
$mysqli->close();
?>

</body>
</html>
