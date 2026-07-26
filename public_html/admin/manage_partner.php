<?php
/*
admin/manage_partner.php
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

// Get total number of partners
$sql = "SELECT COUNT(*) AS total FROM providers_partners";
$result = $conn->query($sql);
$total_partners = $result->fetch_assoc()['total'];

// Calculate total pages
$total_pages = ceil($total_partners / $limit);

// Fetch partners for the current page
$sql = "SELECT * FROM providers_partners LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Function to update partner revenue unpaid and last_updated_revenue
function updatePartnerRevenueUnpaid($conn, $partner_id, $partner_revenue, $partner_revenue_paid) {
    $partner_revenue_unpaid = $partner_revenue - $partner_revenue_paid;
    $sql_update = "UPDATE providers_partners 
                   SET partner_revenue_unpaid = ?, last_updated_revenue = NOW() 
                   WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param('di', $partner_revenue_unpaid, $partner_id);
    $stmt_update->execute();
    $stmt_update->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Partners</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
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
        .td {
    padding: 10px;
    vertical-align: top;
}

.table-striped td {
    border: 1px solid #dee2e6; /* Adds a subtle border for clearer separation */
}

.table-striped th, .table-striped td div {
    margin-bottom: 5px;
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
    <h2>Partners List</h2>

    <div class="card">
        <div class="card-header">
            Providers Partners
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Provider Name</th>
                        <th>Domain</th>
                    
                        <th>Revenue Info</th>
                        <th>Request Approved Date</th>
                       
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): 
                        $partner_id = $row['id'];
                        $partner_revenue = $row['partner_revenue'];
                        $partner_revenue_paid = $row['partner_revenue_paid'];
                        $partner_revenue_unpaid = $partner_revenue - $partner_revenue_paid;

                        // Update the unpaid revenue and last_updated_revenue
                        updatePartnerRevenueUnpaid($conn, $partner_id, $partner_revenue, $partner_revenue_paid);
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['providers_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['providers_domain_url']);
                        
                         echo "<br>API: ".htmlspecialchars($row['api_endpoint']); ?></td>
                       <td>
    <div style="margin-bottom: 5px;">
        <strong>Total Revenue:</strong> <?php echo htmlspecialchars($partner_revenue); ?>
    </div>
    <div style="margin-bottom: 5px;">
        <strong>Paid Revenue:</strong> <?php echo htmlspecialchars($partner_revenue_paid); ?>
    </div>
    <div style="margin-bottom: 5px;">
        <strong>Unpaid Revenue:</strong> <?php echo htmlspecialchars($partner_revenue_unpaid); ?>
    </div>
</td>

                        <td><?php echo "requestdate: ".htmlspecialchars($row['requestdate']); 

                        echo "<br>approved_date ".htmlspecialchars($row['approved_date']); ?></td>
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

<div class="footer">
    &copy; 2024 Admin Panel. All rights reserved.
</div>

<?php include("js_toogle.php"); 


// Close the statement and connection
$stmt->close();
$conn->close();

?>

</body>
</html>
