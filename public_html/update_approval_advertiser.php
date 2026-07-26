<?php
// update_approval_advertiser.php
include("db.php");
include("function.php");
session_start();

// Database connection
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $is_approved_by_advertiser = $_POST['is_approved_by_advertiser'];
    $local_ads_id = (int)$_POST['local_ads_id'];
    $publishers_site_local_id= (int)$_POST['publishers_site_local_id'];

    // Update query
    $query = "UPDATE  mapping_advertisers_ads_publishers_site SET is_approved_by_advertiser = ?, approval_date_advertiser = now() WHERE id = ? AND 	local_ads_id = ? AND publishers_site_local_id = ? " ;
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iiii', $is_approved_by_advertiser, $id , $local_ads_id , $publishers_site_local_id);

   echo "<br>query: ".$query;
   echo "<br>is_approved_by_advertiser: ".$is_approved_by_advertiser;
	echo "<br>id: ".$id;
	echo "<br>local_ads_id: ".$local_ads_id;
	echo "<br>publishers_site_local_id: ".$publishers_site_local_id;


       if ($stmt->execute()) {
        // Redirect to success page or back to the list
        header('Location: view_ads_publishers_mapping.php?local_ads_id='.$local_ads_id.'&status=success');
    		exit();
    } else {
        // Handle error
        echo "Error updating record: " . $conn->error;
    }
}
?>