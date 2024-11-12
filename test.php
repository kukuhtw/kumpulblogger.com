<?php
// Script to test if the mysqlnd extension is active

// Check if mysqlnd is loaded
if (function_exists('mysqli_get_client_stats')) {
    echo "The mysqlnd extension is active.\n";
} else {
    echo "The mysqlnd extension is NOT active.\n";
}

// Alternatively, you can check using phpinfo() to see detailed information
phpinfo(INFO_MODULES);
?>
