<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ad Click Data Update Process</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Ad Click Data Update Process</h1>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Database Connection
            </div>
            <div class="card-body">
                <p>The script establishes connections to the database using both PDO and MySQLi.</p>
                <?php
                include("../db.php");
                include("../function.php");

                // Create a new PDO instance
                $pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db;charset=utf8mb4", $username_db, $password_db);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Database connection using MySQLi
                $mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
                if ($mysqli->connect_error) {
                    error_log("Database connection failed: " . $mysqli->connect_error);
                    exit("Database connection failed.");
                }
                ?>
                <p class="text-success">Database connections established successfully.</p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                Provider Domain URL
            </div>
            <div class="card-body">
                <?php
                // Get providers domain URL based on ID
                $id = 1;
               // $this_providers_domain_url = get_providers_domain_url($mysqli, $id);

                $this_providers_domain_url = get_providers_domain_url_json("../providers_data.json", 1);


                echo "<p>Provider's domain URL: " . $this_providers_domain_url . "</p>";
                ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                Updating ad_clicks Table
            </div>
            <div class="card-body">
                <?php
                // Query to select records where title_ads and site_name are empty
                $query = "SELECT id, local_ads_id, pub_id, ads_providers_domain_url, pubs_providers_domain_url
                          FROM ad_clicks 
                          WHERE title_ads is null or site_domain is null ";

                echo "<p>Query: " . $query . "</p>";

                $result = $mysqli->query($query);

                if ($result->num_rows > 0) {
                    echo "<p>Updating records in ad_clicks table:</p>";
                    echo "<ul>";
                    while ($row = $result->fetch_assoc()) {
                        $ad_click_id = $row['id'];
                        $local_ads_id = $row['local_ads_id'];
                        $pub_id = $row['pub_id'];
                        $ads_providers_domain_url = $row['ads_providers_domain_url'];
                        $pubs_providers_domain_url = $row['pubs_providers_domain_url'];

                        // Get title_ads from advertisers_ads or advertisers_ads_partners based on domain URL
                        if ($this_providers_domain_url == $ads_providers_domain_url) {
                            $ad_query = "SELECT title_ads FROM advertisers_ads WHERE local_ads_id = ?";
                        } else {
                            $ad_query = "SELECT title_ads FROM advertisers_ads_partners WHERE local_ads_id = ?";
                        }
                        $ad_stmt = $pdo->prepare($ad_query);
                        $ad_stmt->execute([$local_ads_id]);
                        $ad_result = $ad_stmt->fetch(PDO::FETCH_ASSOC);
                        $title_ads = $ad_result['title_ads'];

                        // Get site_name and site_domain from publishers_site based on pub_id
                        if ($this_providers_domain_url == $pubs_providers_domain_url) {
                            $site_query = "SELECT site_name, site_domain FROM publishers_site WHERE id = ?";
                            $site_stmt = $pdo->prepare($site_query);
                            $site_stmt->execute([$pub_id]);
                            $site_result = $site_stmt->fetch(PDO::FETCH_ASSOC);
                            $site_name = $site_result['site_name'];
                            $site_domain = $site_result['site_domain'];
                        }

                        // Update ad_clicks table with the fetched title_ads, site_name, and site_domain
                        $update_query = "UPDATE ad_clicks 
                                         SET title_ads = ?, site_name = ?, site_domain = ? 
                                         WHERE id = ?";
                        $update_stmt = $pdo->prepare($update_query);
                        $update_stmt->execute([$title_ads, $site_name, $site_domain, $ad_click_id]);

                        echo "<li>Updated record ID: $ad_click_id</li>";
                        echo "<li>Title Ads: $title_ads</li>";
                        echo "<li>Site Name: $site_name</li>";
                        echo "<li>Site Domain: $site_domain</li>";
                        echo "<hr>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>No records found to update in ad_clicks table.</p>";
                }
                ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                Updating ad_clicks_partner Table
            </div>
            <div class="card-body">
                <?php
                // Query to select records where title_ads and site_name are empty
                $query = "SELECT id, local_ads_id, pub_id, ads_providers_domain_url, pubs_providers_domain_url
                          FROM ad_clicks_partner 
                          WHERE title_ads is null
                          ";
                          //-- 
                echo "<p>Query: " . $query . "</p>";

                $result = $mysqli->query($query);

                if ($result->num_rows > 0) {
                    echo "<p>Updating records in ad_clicks_partner table:</p>";
                    echo "<ul>";
                    while ($row = $result->fetch_assoc()) {
                        $ad_click_id = $row['id'];
                        $local_ads_id = $row['local_ads_id'];
                        $pub_id = $row['pub_id'];
                        $ads_providers_domain_url = $row['ads_providers_domain_url'];
                        $pubs_providers_domain_url = $row['pubs_providers_domain_url'];

                        // Get title_ads from advertisers_ads or advertisers_ads_partners based on domain URL
                        if ($this_providers_domain_url == $ads_providers_domain_url) {
                            $ad_query = "SELECT title_ads FROM advertisers_ads WHERE local_ads_id = ?";
                        } else {
                            $ad_query = "SELECT title_ads FROM advertisers_ads_partners WHERE local_ads_id = ?";
                        }
                        $ad_stmt = $pdo->prepare($ad_query);
                        $ad_stmt->execute([$local_ads_id]);
                        $ad_result = $ad_stmt->fetch(PDO::FETCH_ASSOC);
                        $title_ads = $ad_result['title_ads'];

                        // Get site_name and site_domain from publishers_site_partners based on pub_id
                        if ($this_providers_domain_url != $pubs_providers_domain_url) {
                            $site_query = "SELECT site_name, site_domain FROM publishers_site_partners WHERE id = ?";
                            $site_stmt = $pdo->prepare($site_query);
                            $site_stmt->execute([$pub_id]);
                            $site_result = $site_stmt->fetch(PDO::FETCH_ASSOC);
                            $site_name = $site_result['site_name'];
                            $site_domain = $site_result['site_domain'];
                        }

                        // Update ad_clicks_partner table with the fetched title_ads, site_name, and site_domain
                        $update_query = "UPDATE ad_clicks_partner 
                                         SET title_ads = ?, site_name = ?, site_domain = ? 
                                         WHERE id = ?";
                        $update_stmt = $pdo->prepare($update_query);
                        $update_stmt->execute([$title_ads, $site_name, $site_domain, $ad_click_id]);

                        echo "<li>publishers_site_partners:  Updated record ID: $ad_click_id</li>";
                        echo "<li>Title Ads: $title_ads</li>";
                        echo "<li>Site Name: $site_name</li>";
                        echo "<li>Site Domain: $site_domain</li>";
                        echo "<hr>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>No records found to update in ad_clicks_partner table.</p>";
                }

                $mysqli->close();
                $pdo = null;
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>