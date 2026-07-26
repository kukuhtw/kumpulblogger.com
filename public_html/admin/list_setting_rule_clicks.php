<?php
// admin/list_setting_rule_clicks.php
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

// Query to fetch the setting rules
$query = "SELECT * FROM setting_rule_clicks";
$result = $mysqli->query($query);

if (!$result) {
    error_log("Query failed: " . $mysqli->error);
    exit("Error fetching data.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Setting Rule Clicks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include("style_toogle.php"); ?>
</head>
<body>

<div class="admin-navbar">
    <a class="brand" href="dashboard_admin.php">Admin Dashboard</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
</div>

<?php include("sidebar_menu.php"); ?>

<main class="admin-main" id="mainContent">
    <div class="card">
        <div class="card-header">
            List of Setting Rules
        </div>
        <div class="card-body">
            <?php
            // Check if there are any records
            if ($result->num_rows > 0) {
                // Display the records in a table format using Bootstrap table
                echo '<div class="table-responsive"><table class="table table-striped table-bordered">';
                echo "<thead><tr><th>ID</th><th>Rule Name</th><th>Threshold</th><th>Description</th></tr></thead>";
                echo "<tbody>";

                // Loop through the records and display each row
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['rule_name']) . "</td>";
                    echo "<td><a href='#' class='edit-threshold' data-id='" . $row['id'] . "' data-threshold='" . $row['threshold'] . "'>" . htmlspecialchars($row['threshold']) . "</a></td>";
                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                    echo "</tr>";
                }

                echo "</tbody></table></div>";
            } else {
                // If no records are found
                echo "<div class='alert alert-warning'>No setting rules found.</div>";
            }
            ?>
        </div>
    </div>
</main>

<!-- Modal for Editing Threshold -->
<div class="modal fade" id="editThresholdModal" tabindex="-1" aria-labelledby="editThresholdLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editThresholdLabel">Edit Threshold</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="update_threshold.php" method="post">
                <?php echo admin_csrf_field(); ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="threshold" class="form-label">Threshold</label>
                        <input type="number" class="form-control" id="threshold" name="threshold" required>
                        <input type="hidden" id="ruleId" name="rule_id">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Trigger modal when a threshold link is clicked
    document.querySelectorAll('.edit-threshold').forEach(item => {
        item.addEventListener('click', function(event) {
            event.preventDefault();
            const threshold = this.getAttribute('data-threshold');
            const id = this.getAttribute('data-id');
            document.getElementById('threshold').value = threshold;
            document.getElementById('ruleId').value = id;
            var editThresholdModal = new bootstrap.Modal(document.getElementById('editThresholdModal'));
            editThresholdModal.show();
        });
    });
</script>

<?php include("js_toogle.php"); ?>

<?php
$mysqli->close();
include("footer.php");
?>

</body>
</html>
