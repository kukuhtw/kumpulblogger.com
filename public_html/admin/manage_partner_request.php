<?php
/*
admin/manage_partner_request.php
*/
session_start();

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    // If not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

// Include the database connection
include("../db.php");

$loginemail_admin = $_SESSION['loginemail_admin'];

// Set default page number and items per page
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

// Get total number of provider requests
$sql = "SELECT COUNT(*) AS total FROM providers_request";
$result = $conn->query($sql);
$total_requests = $result->fetch_assoc()['total'];

// Fetch requests for the current page
$sql = "SELECT * FROM providers_request LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Calculate total pages
$total_pages = ceil($total_requests / $limit);

// Close the statement and connection
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Partner Requests</title>
   <?php include("style_toogle.php") ?>
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
    </style>
</head>

<body>

<div class="navbar">
    Admin Dashboard
    <a href="logout.php" style="float:right;">Logout</a>
</div>

<?php include("sidebar_menu.php"); ?>


<div class="container" id="mainContent"> 
    
    <div class="content">
        <h2>Providers Request List</h2>

        <div class="card">
            <div class="card-header">
                Providers Request
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Request From</th>
                            <th>Provider Domain</th>
                            <th>Target Domain</th>
                            <th>API Endpoint</th>
                             <th>Status Follow Up</th>
                            <th>Request Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): 

                            $id = $row['id'];
                    $is_followup = $row['is_followup'];
                    $link_approval="";
                    if ($is_followup==0) {
                         $link_approval = "<a href='approval_join_force.php?id=".$id."'>Approve</a>";
                    }
                            

                            ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['request_from']); ?></td>
                            <td><?php echo htmlspecialchars($row['providers_domain_url']); ?></td>
                            <td><?php echo htmlspecialchars($row['target_providers_domain_url']); ?></td>
                            <td><?php echo htmlspecialchars($row['api_endpoint']); ?></td>
                            <td><?php 


                            echo htmlspecialchars($row['is_followup']); 
                            echo "<br>".$link_approval;

                        ?></td>
                            <td><?php echo htmlspecialchars($row['request_date']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Pagination Links -->
                <nav>
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="footer">
    &copy; 2024 Admin Panel. All rights reserved.
</div>
<?php include("js_toogle.php"); ?>

</body>
</html>
