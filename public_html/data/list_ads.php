<?php
// Include database connection
include("../db.php");

// Create a connection to the MySQL database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check the connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Default sorting settings
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'title_ads'; // Default to sorting by ad title
$sort_order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'DESC' : 'ASC'; // Default to ascending order

// Validate the sorting column
$valid_sort_columns = [
    'title_ads', 'site_name', 'site_domain', 'rate_text_ads', 
    'budget_per_click_textads', 'is_published', 'is_expired', 
    'pubs_providers_name', 'ads_providers_name', 'is_approved_by_publisher', 
    'is_approved_by_advertiser'
];
if (!in_array($sort_column, $valid_sort_columns)) {
    $sort_column = 'title_ads';
}

// Fetch the data from the database, sorted by the specified column
$sql = "SELECT * FROM mapping_advertisers_ads_publishers_site ORDER BY $sort_column $sort_order";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
    echo "<style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        th, td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #007BFF;
            color: #ffffff;
            cursor: pointer;
        }
        th a {
            color: #ffffff;
            text-decoration: none;
        }
        th a:hover {
            text-decoration: underline;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
    </style>";

    echo "<table>";
    echo "<tr>";
    echo "<th><a href='?sort=title_ads&order=" . ($sort_order === 'ASC' ? 'desc' : 'asc') . "'>Ad Title</a></th>";
    echo "<th><a href='?sort=site_name&order=" . ($sort_order === 'ASC' ? 'desc' : 'asc') . "'>Site Title</a></th>";
    echo "<th><a href='?sort=site_domain&order=" . ($sort_order === 'ASC' ? 'desc' : 'asc') . "'>Site Domain</a></th>";
    echo "<th><a href='?sort=rate_text_ads&order=" . ($sort_order === 'ASC' ? 'desc' : 'asc') . "'>Rate (Text Ads)</a></th>";
    echo "<th><a href='?sort=budget_per_click_textads&order=" . ($sort_order === 'ASC' ? 'desc' : 'asc') . "'>Budget Per Click (Text Ads)</a></th>";
    echo "<th><a href='?sort=is_published&order=" . ($sort_order === 'ASC' ? 'desc' : 'asc') . "'>Published</a></th>";
    echo "<th><a href='?sort=is_expired&order=" . ($sort_order === 'ASC' ? 'desc' : 'asc') . "'>Expired</a></th>";
    echo "<th><a href='?sort=pubs_providers_name&order=" . ($sort_order === 'ASC' ? 'desc' : 'asc') . "'>Publisher Provider Name</a></th>";
    echo "<th><a href='?sort=ads_providers_name&order=" . ($sort_order === 'ASC' ? 'desc' : 'asc') . "'>Advertiser Provider Name</a></th>";
    echo "<th><a href='?sort=is_approved_by_publisher&order=" . ($sort_order === 'ASC' ? 'desc' : 'asc') . "'>Approved by Publisher</a></th>";
    echo "<th><a href='?sort=is_approved_by_advertiser&order=" . ($sort_order === 'ASC' ? 'desc' : 'asc') . "'>Approved by Advertiser</a></th>";
    echo "<th>Landing Page</th>";
    echo "<th>Published Date</th>";
    echo "</tr>";

    // Output data of each row
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['title_ads']) . "</td>";
        echo "<td>" . htmlspecialchars($row['site_name']) . "</td>";
        echo "<td><a href='http://" . htmlspecialchars($row['site_domain']) . "' target='_blank'>" . htmlspecialchars($row['site_domain']) . "</a></td>";
        echo "<td>" . htmlspecialchars($row['rate_text_ads']) . "</td>";
        echo "<td>" . htmlspecialchars($row['budget_per_click_textads']) . "</td>";
        echo "<td>" . ($row['is_published'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . ($row['is_expired'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . htmlspecialchars($row['pubs_providers_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['ads_providers_name']) . "</td>";
        echo "<td>" . ($row['is_approved_by_publisher'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . ($row['is_approved_by_advertiser'] ? 'Yes' : 'No') . "</td>";
        echo "<td><a href='" . htmlspecialchars($row['landingpage_ads']) . "' target='_blank'>" . htmlspecialchars($row['landingpage_ads']) . "</a></td>";
        echo "<td>" . htmlspecialchars($row['published_date']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No records found.</p>";
}

// Close the database connection
$mysqli->close();
?>
