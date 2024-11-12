<?php
// list_setting_list_browser_banned.php
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

// Fetch login email from session
$loginemail_admin = $_SESSION['loginemail_admin'];

// Database connection using MySQLi
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit("Database connection failed.");
}

// Define items per page
$items_per_page = 20;

// Get the current page number from the query string, default to 1
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1;

// Calculate the offset for the SQL query
$offset = ($page - 1) * $items_per_page;

// Get search query if exists
$search = isset($_GET['search']) ? $mysqli->real_escape_string($_GET['search']) : "";

// Fetch the list of banned browsers with pagination and optional search
if ($search) {
    $query = "SELECT * FROM list_browser_banned WHERE browser_agent LIKE ? OR reason LIKE ? LIMIT ?, ?";
    $search_term = '%' . $search . '%';
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ssii", $search_term, $search_term, $offset, $items_per_page);
} else {
    $query = "SELECT * FROM list_browser_banned LIMIT ?, ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ii", $offset, $items_per_page);
}
$stmt->execute();
$result = $stmt->get_result();
$banned_list = $result->fetch_all(MYSQLI_ASSOC);

// Fetch the total number of rows for pagination
if ($search) {
    $total_rows_query = "SELECT COUNT(*) AS total FROM list_browser_banned WHERE browser_agent LIKE ? OR reason LIKE ?";
    $stmt_total = $mysqli->prepare($total_rows_query);
    $stmt_total->bind_param("ss", $search_term, $search_term);
    $stmt_total->execute();
    $total_rows_result = $stmt_total->get_result();
} else {
    $total_rows_query = "SELECT COUNT(*) AS total FROM list_browser_banned";
    $total_rows_result = $mysqli->query($total_rows_query);
}
$total_rows = $total_rows_result->fetch_assoc()['total'];

// Calculate total pages
$total_pages = ceil($total_rows / $items_per_page);

// Add a new banned browser agent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_browser_agent'])) {
    $browser_agent = $mysqli->real_escape_string($_POST['browser_agent']);
    $reason = $mysqli->real_escape_string($_POST['reason']);
    $date_banned = date('Y-m-d H:i:s');

    $insert_query = "INSERT INTO list_browser_banned (browser_agent, reason, date_banned) VALUES (?, ?, ?)";
    $insert_stmt = $mysqli->prepare($insert_query);
    $insert_stmt->bind_param("sss", $browser_agent, $reason, $date_banned);
    $insert_stmt->execute();

    header('Location: list_setting_list_browser_banned.php');
    exit;
}

// Edit a banned browser agent
if (isset($_POST['edit_browser_agent'])) {
    $id = $_POST['id'];
    $browser_agent = $mysqli->real_escape_string($_POST['browser_agent']);
    $reason = $mysqli->real_escape_string($_POST['reason']);

    $update_query = "UPDATE list_browser_banned SET browser_agent = ?, reason = ? WHERE id = ?";
    $update_stmt = $mysqli->prepare($update_query);
    $update_stmt->bind_param("ssi", $browser_agent, $reason, $id);
    $update_stmt->execute();

    header('Location: list_setting_list_browser_banned.php');
    exit;
}

// Delete a banned browser agent
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    $delete_query = "DELETE FROM list_browser_banned WHERE id = ?";
    $delete_stmt = $mysqli->prepare($delete_query);
    $delete_stmt->bind_param("i", $delete_id);
    $delete_stmt->execute();

    header('Location: list_setting_list_browser_banned.php');
    exit;
}
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
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            width: 100%;
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
            width: 100%;
        }
        .card {
            margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
        }
        .card-header {
            background-color: #28a745;
            color: white;
            font-size: 24px;
            text-align: center;
            width: 100%;
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
        .pagination {
            margin-top: 20px;
            justify-content: center;
        }
    </style>
</head>
<body>

<?php include("sidebar_menu.php"); ?>

<div class="container" id="mainContent">
        
    <div class="card" width="100%">
        <div class="card-header">
            Banned Browser List
        </div>
        <div class="card-body">
            <!-- Form to search banned browsers -->
            <form method="GET" class="mb-4 row g-3">
                <div class="col-md-10">
                    <input type="text" class="form-control" name="search" placeholder="Search by Browser Agent or Reason" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>

            <!-- Form to add a new banned browser -->
            <form method="POST" class="mb-4 row g-3">
                <div class="col-md-5">
                    <input type="text" class="form-control" name="browser_agent" placeholder="Browser Agent" required>
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control" name="reason" placeholder="Reason" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="add_browser_agent" class="btn btn-success w-100">Add</button>
                </div>
            </form>

            <!-- Table to display banned browsers -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Browser Agent</th>
                            <th>Reason</th>
                            <th>Date Banned</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($banned_list) > 0): ?>
                            <?php foreach ($banned_list as $ban): ?>
                                <tr>
                                    <td><?= $ban['id']; ?></td>
                                    <td>
                                        <?= substr(htmlspecialchars($ban['browser_agent']), 0, 5); ?>...
                                        <a href="#" class="view-more" data-agent="<?= htmlspecialchars($ban['browser_agent']); ?>" data-bs-toggle="modal" data-bs-target="#browserAgentModal">View More</a>
                                    </td>
                                    <td><?= htmlspecialchars($ban['reason']); ?></td>
                                    <td><?= $ban['date_banned']; ?></td>
                                    <td>
                                        <div class="d-flex">
                                            <!-- Form to edit a banned browser -->
                                            <form method="POST" class="me-2">
                                                <input type="hidden" name="id" value="<?= $ban['id']; ?>">
                                                <input type="text" class="form-control mb-2" name="browser_agent" value="<?= htmlspecialchars($ban['browser_agent']); ?>" required>
                                                <input type="text" class="form-control mb-2" name="reason" value="<?= htmlspecialchars($ban['reason']); ?>" required>
                                                <button type="submit" name="edit_browser_agent" class="btn btn-warning btn-sm">Edit</button>
                                            </form>

                                            <!-- Link to delete a banned browser -->
                                            <a href="list_setting_list_browser_banned.php?delete_id=<?= $ban['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
    <h1>Browser Agent Block Info</h1>
    <p>Aplikasi ini memblokir browser agent yang mengandung kata-kata tertentu untuk mencegah akses bot yang tidak diinginkan. Kata-kata yang diblokir adalah sebagai berikut:</p>
    <ul>
        <li><strong>Bot</strong></li>
        <li><strong>crawler</strong></li>
        <li><strong>spider</strong></li>
        <li><strong>archive</strong></li>
    </ul>
    <p>Berikut adalah fungsi PHP yang digunakan untuk memeriksa browser agent dan memblokir akses:</p>
    <code>
        function isBrowserAgentBot($browser_agent) {<br>
        &nbsp;&nbsp;&nbsp;&nbsp;$banned_keywords = ['Bot', 'crawler', 'spider', 'archive'];<br><br>
        &nbsp;&nbsp;&nbsp;&nbsp;foreach ($banned_keywords as $keyword) {<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;if (stripos($browser_agent, $keyword) !== false) {<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;return true;<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}<br>
        &nbsp;&nbsp;&nbsp;&nbsp;}<br><br>
        &nbsp;&nbsp;&nbsp;&nbsp;return false;<br>
        }
    </code>

            </div>

            <!-- Pagination Links -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1; ?>&search=<?= htmlspecialchars($search) ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i; ?>&search=<?= htmlspecialchars($search) ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1; ?>&search=<?= htmlspecialchars($search) ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Modal for showing the full browser agent -->
    <div class="modal fade" id="browserAgentModal" tabindex="-1" aria-labelledby="browserAgentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="browserAgentModalLabel">Browser Agent Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="fullBrowserAgent"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<?php include("js_toogle.php"); ?>

<script>
    // JavaScript to load the full browser agent in the modal
    document.querySelectorAll('.view-more').forEach(function(element) {
        element.addEventListener('click', function() {
            const fullAgent = this.getAttribute('data-agent');
            document.getElementById('fullBrowserAgent').innerText = fullAgent;
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php
// Close connection
$mysqli->close();
?>