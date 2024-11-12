<?php
/*
cronjob/rekap_harian.php
*/

include("../db.php");
include("../function.php");
$sumber_data = "ad_clicks";
// Create a new PDO instance
$pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db;charset=utf8mb4", $username_db, $password_db);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Database connection using MySQLi
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit("Database connection failed.");
}

// Fungsi untuk mengubah nama hari ke bahasa Indonesia
function getHariIndonesia($hariInggris) {
    $hari = array(
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    );
    return $hari[$hariInggris];
}

// Mendapatkan domain URL dari ID
$id = 1;
//$this_providers_domain_url = get_providers_domain_url($mysqli, $id);
$this_providers_domain_url = get_providers_domain_url_json("../providers_data.json", 1);


// Filter untuk dua hari terakhir
$date_two_days_ago = date('Y-m-d', strtotime('-200 days'));

// Query untuk mengambil data dari kedua tabel berdasarkan kriteria dua hari terakhir
$query = "
    SELECT 
        DATE(acp.click_time) AS tanggal_klik, 
        acp.local_ads_id, 
        acp.ads_providers_domain_url, 
        SUM(acp.revenue_publishers) AS revenue_publishers, 
        SUM(acp.revenue_adnetwork_local) AS revenue_adnetwork_local, 
        SUM(acp.revenue_adnetwork_partner) AS revenue_adnetwork_partner, 
        SUM(acp.revenue_publishers + acp.revenue_adnetwork_local + acp.revenue_adnetwork_partner) AS total_spending, 
        COUNT(*) AS jumlah_klik
    FROM ad_clicks acp
    WHERE acp.isaudit = 1 AND acp.is_reject = 0 AND DATE(acp.click_time) >= ?
    GROUP BY DATE(acp.click_time), acp.local_ads_id, acp.ads_providers_domain_url
    
";


// Prepare and execute query with date filter for the last two days
$stmt = $pdo->prepare($query);
$stmt->execute([$date_two_days_ago]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// HTML Output
echo "<!DOCTYPE html>";
echo "<html lang='id'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Rekap Harian Biaya Spending Advertiser</title>";
echo "<style>";
echo "body {font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f0f2f5; color: #333;}";
echo "h1 {color: #0056b3; text-align: center; padding: 20px 0;}";
echo ".container {max-width: 1200px; margin: 30px auto; padding: 20px; background-color: #fff; border-radius: 10px; box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);}";
echo ".section {margin-bottom: 40px;}";
echo ".section h2 {color: #0288D1; font-size: 1.75em; margin-bottom: 10px;}";
echo ".log {padding: 15px; background-color: #f9f9c5; border-left: 5px solid #f7c600; border-radius: 8px; margin-bottom: 30px; font-size: 1.1em;}";
echo ".highlight {color: green; font-weight: bold;}";
echo ".error {color: red; font-weight: bold;}";
echo ".table-wrapper {overflow-x: auto;}";
echo ".table {width: 100%; border-collapse: collapse; margin-bottom: 20px;}";
echo ".table th, .table td {padding: 12px 15px; border: 1px solid #ddd; text-align: left;}";
echo ".table th {background-color: #0056b3; color: #fff; text-align: center;}";
echo ".table td {background-color: #f9f9f9;}";
echo ".table tbody tr:hover {background-color: #f1f1f1;}";
echo "footer {text-align: center; padding: 20px; background-color: #0056b3; color: #fff; font-size: 0.9em; border-top-left-radius: 8px; border-top-right-radius: 8px;}";
echo ".process {background-color: #e9f2fb; padding: 20px; margin-bottom: 30px; border-left: 5px solid #0288D1; font-size: 1.1em; border-radius: 8px;}";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<div class='container'>";
echo "<h1>Rekap Harian Biaya Spending Advertiser</h1>";

// Tambahkan informasi tentang proses yang sedang berjalan
echo "<div class='process'>";
echo "<h2>Informasi Proses</h2>";
echo "<p>Proses ini secara otomatis melakukan rekap harian untuk biaya spending dari advertiser berdasarkan klik selama dua hari terakhir.</p>";
echo "</div>";

// Data untuk file JSON dan CSV
$data_rekap = [];

if (!empty($result)) {
    // Tampilkan tabel hasil rekapitulasi
    echo "<div class='section'>";
    echo "<h2>Hasil Rekapitulasi</h2>";
    echo "<div class='table-wrapper'>";
    echo "<table class='table'>
            <thead>
                <tr>
                    <th>Tanggal Klik</th>
                    <th>Local Ads ID</th>
                    <th>Ads Provider Domain URL</th>
                    <th>Sumber Data</th>
                    <th>Revenue Publishers</th>
                    <th>Revenue AdNetwork Local</th>
                    <th>Revenue AdNetwork Partner</th>
                    <th>Total Spending</th>
                    <th>Jumlah Klik</th>
                    <th>Title Ads</th>
                    <th>Landingpage Ads</th>
                    <th>Budget Allocation</th>
                </tr>
            </thead>";
    echo "<tbody>";

    foreach ($result as $row) {
        $hari_klik = getHariIndonesia(date('l', strtotime($row["tanggal_klik"])));
        $tanggal_dan_hari = date('Y-m-d', strtotime($row["tanggal_klik"]));

        // Ambil data dari advertisers_ads atau advertisers_ads_partners
        $ads_providers_domain_url = $row["ads_providers_domain_url"];
        
        if ($this_providers_domain_url == $row["ads_providers_domain_url"]) {
            $ads_query = "SELECT title_ads, landingpage_ads, budget_allocation FROM advertisers_ads WHERE local_ads_id = ? AND providers_domain_url = ?";
        } else {
            $ads_query = "SELECT title_ads, landingpage_ads, budget_allocation FROM advertisers_ads_partners WHERE local_ads_id = ? AND providers_domain_url = ?";
        }
        $stmt = $pdo->prepare($ads_query);
        $stmt->execute([$row["local_ads_id"], $row["ads_providers_domain_url"]]);
        $ads_data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Insert atau update data ke dalam rekap_harian
        $check_query = "SELECT COUNT(*) FROM rekap_harian WHERE tanggal_klik = ? 
        AND local_ads_id = ? 
        AND ads_providers_domain_url = ?
        AND sumber_data = ?
        ";
        $stmt_check = $pdo->prepare($check_query);
        $stmt_check->execute([$tanggal_dan_hari, $row["local_ads_id"], 
            $row["ads_providers_domain_url"], 
            $sumber_data
                    ]);
        $exists = $stmt_check->fetchColumn();
            $report ="";
        if ($exists == 0) {
            // Simpan data ke tabel rekap_harian
       
        $insert_query = "
                INSERT INTO rekap_harian (
                    tanggal_klik, local_ads_id, ads_providers_domain_url, sumber_data, 
                    sumber_data_url, 
                    revenue_publishers, revenue_adnetwork_local, revenue_adnetwork_partner, 
                    total_spending, jumlah_klik, title_ads, landingpage_ads, budget_allocation
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $local_ads_id = $row["local_ads_id"];
            $ads_providers_domain_url = $row["ads_providers_domain_url"];
            

            $report .="INSERT INTO `rekap_harian` data local_ads_id: ".$local_ads_id. " ads_providers_domain_url: ".$ads_providers_domain_url;

            $stmt_insert = $pdo->prepare($insert_query);
            
              $stmt_insert->execute([
                $tanggal_dan_hari, $row["local_ads_id"], $row["ads_providers_domain_url"], $sumber_data,
                $this_providers_domain_url, 
                $row["revenue_publishers"], $row["revenue_adnetwork_local"], $row["revenue_adnetwork_partner"],
                $row["total_spending"], $row["jumlah_klik"], $ads_data["title_ads"], $ads_data["landingpage_ads"], $ads_data["budget_allocation"]
            ]);
              echo "<tr class='highlight'><td colspan='12'>Data baru berhasil di-insert untuk iklan {$ads_data['title_ads']}</td></tr>";
        } else {
            $update_query = "
            UPDATE rekap_harian
            SET revenue_publishers = ?, revenue_adnetwork_local = ?, revenue_adnetwork_partner = ?, 
                total_spending = ?, jumlah_klik = ?, title_ads = ?, budget_allocation = ?
            WHERE tanggal_klik = ? AND local_ads_id = ? AND ads_providers_domain_url = ? AND sumber_data = ?
            ";
             $local_ads_id = $row["local_ads_id"];
            $ads_providers_domain_url = $row["ads_providers_domain_url"];
            
            $report .="<br>Diupdate karena data Table rekap_harian local_ads_id: ".$local_ads_id.", ads_providers_domain_url: ".$ads_providers_domain_url."<br>dan sumber_data_url !=".$ads_providers_domain_url." berjumlah: ".$exists."<br>Update data rekap_harian local_ads_id: ".$local_ads_id."<br> dan ads_providers_domain_url: ".$ads_providers_domain_url. " yang tanggal_klik: ".$tanggal_dan_hari;


            $stmt_update = $pdo->prepare($update_query);
            $stmt_update->execute([
                
                $row["revenue_publishers"], $row["revenue_adnetwork_local"], $row["revenue_adnetwork_partner"],
                $row["total_spending"], $row["jumlah_klik"], $ads_data["title_ads"], $ads_data["budget_allocation"],
                $tanggal_dan_hari, $row["local_ads_id"], $row["ads_providers_domain_url"], $sumber_data
            ]);

            echo "<tr class='highlight'><td colspan='12'>Data untuk iklan {$ads_data['title_ads']} telah diperbarui.".$report."</td></tr>";
        }

        // Tampilkan data di dalam tabel
        echo "<tr>
                <td>{$tanggal_dan_hari}</td>
                <td>{$row['local_ads_id']}</td>
                <td>{$row['ads_providers_domain_url']}</td>
                <td>{$sumber_data}</td>
                <td>{$row['revenue_publishers']}</td>
                <td>{$row['revenue_adnetwork_local']}</td>
                <td>{$row['revenue_adnetwork_partner']}</td>
                <td>{$row['total_spending']}</td>
                <td>{$row['jumlah_klik']}</td>
                <td>{$ads_data['title_ads']}</td>
                <td>{$ads_data['landingpage_ads']}</td>
                <td>{$ads_data['budget_allocation']}</td>
              </tr>";

        // Tambahkan data ke array untuk file JSON dan CSV
        $data_rekap[] = [
            "tanggal_klik" => $tanggal_dan_hari,
            "hari" => $hari_klik,
            "local_ads_id" => $row["local_ads_id"],
            "ads_providers_domain_url" => $row["ads_providers_domain_url"],
            "sumber_data" => $sumber_data,
            "revenue_publishers" => $row["revenue_publishers"],
            "revenue_adnetwork_local" => $row["revenue_adnetwork_local"],
            "revenue_adnetwork_partner" => $row["revenue_adnetwork_partner"],
            "total_spending" => $row["total_spending"],
            "jumlah_klik" => $row["jumlah_klik"],
            "title_ads" => $ads_data["title_ads"],
            "landingpage_ads" => $ads_data["landingpage_ads"],
            "budget_allocation" => $ads_data["budget_allocation"]
        ];
    }
    echo "</tbody></table>";
    echo "</div>"; // .table-wrapper
} else {
    echo "<p class='error'>Tidak ada data yang ditemukan untuk dua hari terakhir.</p>";
}

echo "</div>"; // .section

// Generate file JSON
//file_put_contents('../JSON/rekap_harian.json', json_encode($data_rekap, JSON_PRETTY_PRINT));

// Generate file CSV
//$csv_file = fopen('../rekap_harian.csv', 'w');
//fputcsv($csv_file, array_keys($data_rekap[0])); // Header CSV

//foreach ($data_rekap as $row) {
   // fputcsv($csv_file, $row);
//}
//fclose($csv_file);

// Tutup koneksi
$mysqli->close();

echo "<footer><p>&copy; 2024 - Rekap Harian Biaya Spending Advertiser</p></footer>";
echo "</div>"; // .container
echo "</body>";
echo "</html>";
?>
