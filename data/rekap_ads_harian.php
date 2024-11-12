<?php

// DATA/rekap_ads_harian.php

include("../db.php"); // Koneksi database
include("../function.php");

// Create a connection to the MySQL database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Check the connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
 $grand_total_spending = 0;
               $grand_total_revenue_adnetwork_local = 0;  
               $grand_total_revenue_adnetwork_partner = 0; 

$id = 1;
$this_providers_domain_url = get_providers_domain_url($mysqli, $id);

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

 // Handle form submission
$tanggal_klik = isset($_POST['tanggal_klik']) ? $_POST['tanggal_klik'] : 'all';
$tanggal_spesifik = isset($_POST['tanggal_spesifik']) ? $_POST['tanggal_spesifik'] : '';
$local_ads_id_ads_provider_domain = isset($_POST['local_ads_id_ads_provider_domain']) ? $_POST['local_ads_id_ads_provider_domain'] : 'all';
$sumber_data = isset($_POST['sumber_data']) ? $_POST['sumber_data'] : 'all';
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Handle form submission
$tanggal_klik = isset($_POST['tanggal_klik']) ? $_POST['tanggal_klik'] : 'all';
$tanggal_spesifik = isset($_POST['tanggal_spesifik']) ? $_POST['tanggal_spesifik'] : '';
$local_ads_id_ads_provider_domain = isset($_POST['local_ads_id_ads_provider_domain']) ? $_POST['local_ads_id_ads_provider_domain'] : 'all';
$sumber_data = isset($_POST['sumber_data']) ? $_POST['sumber_data'] : 'all';


    // Query dasar
    $query = "SELECT * FROM rekap_harian WHERE 1=1";

    
    // Filter tanggal
    if (!empty($tanggal_klik)) {
        if ($tanggal_klik == "all") {
            // Tidak menambahkan kondisi, ambil semua tanggal
        } else {
            // Ambil input tanggal spesifik dari form
            $tanggal_spesifik = $_POST['tanggal_spesifik'];

            // Pastikan tanggal dalam format YYYY-MM-DD
            if (!empty($tanggal_spesifik)) {
                $query .= " AND tanggal_klik = '$tanggal_spesifik'";
            } else {
                echo "Tanggal spesifik tidak valid!";
            }
        }
    }




  // Filter local_ads_id dan ads_providers_domain_url
    if (!empty($local_ads_id_ads_provider_domain) && $local_ads_id_ads_provider_domain != "all") {
        // Pisahkan nilai menjadi local_ads_id dan ads_providers_domain_url
        list($local_ads_id, $ads_providers_domain_url) = explode("~", $local_ads_id_ads_provider_domain);

        $query .= " AND local_ads_id = $local_ads_id AND ads_providers_domain_url = '$ads_providers_domain_url'";
    }

    // Filter sumber_data
    if (!empty($sumber_data) && $sumber_data != "all") {
        $query .= " AND sumber_data = '$sumber_data'";
    }

    // Jalankan query
    $result = $mysqli->query($query);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Harian</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        input[type="submit"] {
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h1>Rekap Harian Transaksi Klik pada Local AdNetwork <?php echo $this_providers_domain_url ?></h1>

    <!-- Form untuk filter -->
   

<form method="post" action="">
    <label for="tanggal_klik">Tanggal Klik:</label>
    <select name="tanggal_klik" id="tanggal_klik">
        <option value="all" <?php echo ($tanggal_klik == 'all') ? 'selected' : ''; ?>>Semua Tanggal</option>
        <option value="specific" <?php echo ($tanggal_klik == 'specific') ? 'selected' : ''; ?>>Pilih Tanggal Spesifik</option>
    </select>

    <!-- Input untuk tanggal spesifik -->
    <input type="date" name="tanggal_spesifik" id="tanggal_spesifik" value="<?php echo $tanggal_spesifik; ?>" style="display: <?php echo ($tanggal_klik == 'specific') ? 'inline' : 'none'; ?>;">

    <br><br>

    <label for="local_ads_id">ID Iklan:</label>
    <select name="local_ads_id_ads_provider_domain">
        <option value="all" <?php echo ($local_ads_id_ads_provider_domain == 'all') ? 'selected' : ''; ?>>Semua ID Iklan</option>
        <?php
        // Ambil semua local_ads_id dari database
        $ads_query = "SELECT DISTINCT local_ads_id, ads_providers_domain_url , title_ads FROM rekap_harian";
        $ads_result = $mysqli->query($ads_query);
        while ($row = $ads_result->fetch_assoc()) {
            $value = $row['local_ads_id'] . "~" . $row['ads_providers_domain_url'];
            $selected = ($local_ads_id_ads_provider_domain == $value) ? 'selected' : '';
            echo "<option value='" . $value . "' $selected>" . $row['local_ads_id'] . " - " . $row['title_ads'] . " - " . $row['ads_providers_domain_url'] . "</option>";
        }
        ?>
    </select>

    <br><br>

    <label for="sumber_data">Sumber Klik:</label>
    <select name="sumber_data">
        <option value="all" <?php echo ($sumber_data == 'all') ? 'selected' : ''; ?>>Semua</option>
        <option value="ad_clicks" <?php echo ($sumber_data == 'ad_clicks') ? 'selected' : ''; ?>>Lokal (ad_clicks)</option>
        <option value="ad_clicks_partner" <?php echo ($sumber_data == 'ad_clicks_partner') ? 'selected' : ''; ?>>Partner (ad_clicks_partner)</option>
    </select>

    <br><br>

    <input type="submit" value="Search">
</form>


    <br>

    <!-- Tampilkan hasil pencarian jika ada -->
    <?php 


    if (isset($result) && $result->num_rows > 0): 

        ?>
        <table>
            <tr>
                <th>Tanggal dan Hari Klik</th>
                <th>Local Ads ID</th>
                <th>Sumber Data</th>
                <th>Total Spending</th>
                <th>Jumlah Klik</th>
                <th>Title Ads</th>
                <th>Landingpage Ads</th>
                <th>Budget Allocation</th>
            </tr>

            <?php 
               

            while ($row = $result->fetch_assoc()): 
              
                // Gabungkan Hari dan Tanggal
                $tanggal = date('d-m-Y', strtotime($row['tanggal_klik']));
                $hari = getHariIndonesia(date('l', strtotime($row['tanggal_klik'])));

                $local_ads_id = $row['local_ads_id'];
                 $ads_providers_domain_url = $row['ads_providers_domain_url'];

                $revenue_publishers = $row['revenue_publishers'];
                $spending_revenue_adnetwork_local = $row['revenue_adnetwork_local'];
                $spending_revenue_adnetwork_partner = $row['revenue_adnetwork_partner'];

                $total_spending = $revenue_publishers + $spending_revenue_adnetwork_local + $spending_revenue_adnetwork_partner;



                $grand_total_revenue_adnetwork_local += $spending_revenue_adnetwork_local; // Use += to accumulate

                 $grand_total_revenue_adnetwork_partner += $spending_revenue_adnetwork_partner; // Use += to accumulate

                  $grand_total_spending += $total_spending; // Use += to accumulate


                $clik_date = DateTime::createFromFormat('d-m-Y', $tanggal);
                $tanggal_formatted = $clik_date->format('Y-m-d');

                if ($row['sumber_data']=="ad_clicks") {
                $report_url="clicks_local_detail.php?local_ads_id=".$local_ads_id."&click_time=".$tanggal_formatted."&ads_providers_domain_url=".$ads_providers_domain_url;
                $report_url="Local";
                }
                else {
                    $report_url="clicks_partner_detail.php?local_ads_id=".$local_ads_id."&click_time=".$tanggal_formatted."&ads_providers_domain_url=".$ads_providers_domain_url;
                    $report_url="Partner";
                }
               

                ?>
                <tr>
                    <td><?php echo "$hari, $tanggal"; ?></td>
                    <td><?php echo $row['local_ads_id']; ?></td>
                    <td><?php echo $report_url ?></td>
                    <td>Rp <?php echo number_format($row['total_spending']); ?></td>
                    <td><?php echo $row['jumlah_klik']; ?></td>
                    <td><?php echo $row['title_ads']; ?></td>
                    <td>
                        
               <a href="<?php echo htmlspecialchars($row['landingpage_ads']); ?>" target="_blank">
                <?php echo htmlspecialchars($row['landingpage_ads']); ?>
                                    </a>
 


                    </td>
                    <td>Rp <?php echo number_format($row['budget_allocation'],2); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php elseif (isset($result)): ?>
        <p>Tidak ada data yang ditemukan.</p>
    <?php endif; ?>
    <p>Grand Total Spending: Rp <?php echo number_format($grand_total_spending); ?>
     - Grand Total adnetwork_local: Rp <?php echo number_format($grand_total_revenue_adnetwork_local); ?> 
     - Grand Total adnetwork partner: Rp <?php echo number_format($grand_total_revenue_adnetwork_partner); ?>
<p>
<a href="../reg.php">Daftar</a> | 
  <a href="../login.php">Login</a> | 
    <a href="../forgot_password.php">Lupa Password?</a>  | 
    <a href="../index.php">Home</a> | 
</p>

<script>
    document.getElementById('tanggal_klik').addEventListener('change', function() {
        if (this.value === 'specific') {
            document.getElementById('tanggal_spesifik').style.display = 'inline';
        } else {
            document.getElementById('tanggal_spesifik').style.display = 'none';
        }
    });
</script>


    <script>
        // Script untuk menampilkan input tanggal spesifik jika user memilih opsi 'Pilih Tanggal Spesifik'
        document.getElementById('tanggal_klik').addEventListener('change', function() {
            if (this.value === 'specific') {
                document.getElementById('tanggal_spesifik').style.display = 'inline';
            } else {
                document.getElementById('tanggal_spesifik').style.display = 'none';
            }
        });
    </script>


</body>
</html>
