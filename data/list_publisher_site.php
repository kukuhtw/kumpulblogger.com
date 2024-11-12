<?php
// DATA/list_publisher_site.php

include("../db.php"); // Koneksi database
include("../function.php");

// Create a connection to the MySQL database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check the connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Pencarian
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah item per halaman
$offset = ($page - 1) * $limit;

// Prepare the SQL statement with placeholders
$sql = "SELECT * FROM publishers_site 
        WHERE site_name LIKE ? OR site_domain LIKE ? OR site_desc LIKE ?
        ORDER BY regdate DESC 
        LIMIT ? OFFSET ?";

// Prepare the statement
$stmt = $mysqli->prepare($sql);

// Bind the parameters
$search_param = "%{$search}%";
$stmt->bind_param('sssii', $search_param, $search_param, $search_param, $limit, $offset);

// Execute the statement
$stmt->execute();

// Get the result
$result = $stmt->get_result();

// Prepare the SQL statement for counting total rows
$total_sql = "SELECT COUNT(*) FROM publishers_site 
              WHERE site_name LIKE ? OR site_domain LIKE ? OR site_desc LIKE ?";

// Prepare the statement
$total_stmt = $mysqli->prepare($total_sql);

// Bind the parameters
$total_stmt->bind_param('sss', $search_param, $search_param, $search_param);

// Execute the statement
$total_stmt->execute();

// Get the result
$total_result = $total_stmt->get_result();
$total_rows = $total_result->fetch_array()[0];
$total_pages = ceil($total_rows / $limit);

// Close the statement for total count
$total_stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publishers Site List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .footer {
            background-color: #343a40;
            color: white;
            padding: 1rem;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">Publisher Site List</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item"><a class="nav-link" href="../reg.php">Daftar</a></li>
                    <li class="nav-item"><a class="nav-link" href="../login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="../forgot_password.php">Lupa Password?</a></li>
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main content -->
    <div class="container my-5">
        <h1 class="text-center mb-4">Publishers Site List</h1>

        <!-- Form Pencarian -->
        <form class="d-flex mb-3" method="GET" action="">
            <input class="form-control me-2" type="search" name="search" placeholder="Cari..." aria-label="Search" value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-outline-success" type="submit">Cari</button>
        </form>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Site Info</th>
                            <th>Rate Text Ads per Klik</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): 

                            $ulasan = $row['ulasan'];
                            $ulasan = str_replace("*","",$ulasan);

                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                

                                <td>
            <a href="<?php echo htmlspecialchars($row['site_domain']); ?>" target="_blank">
                <?php echo htmlspecialchars($row['id']) . ' - ' . htmlspecialchars($row['site_name']) . ' (' . htmlspecialchars($row['site_domain']) . ')'; ?>
            </a>
            <p><?php echo htmlspecialchars($row['site_desc']); ?></p>
            <!-- Tombol untuk menampilkan ulasan dalam modal -->
            <button 
    class="btn btn-primary btn-sm" 
    data-bs-toggle="modal" 
    data-bs-target="#reviewModal" 
    data-review="<?php echo nl2br(htmlspecialchars(addslashes($ulasan))); ?>"
    onclick="showReview(this.getAttribute('data-review'))">
    Lihat Ulasan
</button>


        </td>

                                <td>
                                    Rate Publisher: Rp <?php echo htmlspecialchars(number_format($row['rate_text_ads'], 2)); ?><br>
                                    Harga Jual: Rp <?php echo htmlspecialchars(number_format($row['rate_text_ads'] * 1.5, 2)); ?>
                                </td>
                                <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($row['regdate']))); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <nav aria-label="Page navigation example">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php else: ?>
            <p class="text-center">No records found.</p>
        <?php endif; ?>
    </div>

    <!-- Modal untuk menampilkan ulasan -->
  <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">Ulasan Situs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="reviewContent"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>





<script>
function showReview(reviewContent) {
    document.getElementById('reviewContent').innerHTML = reviewContent;
}
</script>



    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.min.js"></script>
</body>

</html>

<?php
// Close the statement and connection
$stmt->close();
$mysqli->close();
?>
