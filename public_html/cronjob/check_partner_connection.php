<?php

/*
cronjob/check_partner_connection.php
*/
include("../db.php");

// Create a new PDO instance
$pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db;charset=utf8mb4", $username_db, $password_db);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}

// Fungsi untuk memeriksa status URL
function checkUrl($url)
{
    // Cek jika bisa mengakses ok.txt di URL
    $url = rtrim($url, '/') . '/check/ok.txt';

    // Menggunakan cURL untuk memeriksa URL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true); // Tidak mengambil body
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout setelah 10 detik
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Return true jika kode HTTP adalah 200 (OK)
   
    if ($httpcode==200) {
         $up = "<strong><font color=green>".$url." IS UP</font></strong>";
        echo "<br>".$up;
    }
    else {
        $up = "<strong><font color=red>".$url."  IS DOWN</font></strong>";
        echo "<br>".$up;
    }

   // echo "<br><br>response ".$url."= ".$response;
   // echo "<br>httpcode ".$url."= ".$httpcode;
    

    return $httpcode === 200;
}

// Ambil semua providers dari table
$sql = "SELECT id, providers_domain_url, is_hold FROM providers_partners";
$result = $conn->query($sql);

//echo "<br>sql= ".$sql;

if ($result->num_rows > 0) {
    // Loop melalui semua hasil
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $url = $row['providers_domain_url'];
        $is_hold = $row['is_hold'];

       // echo "<br><br>url= ".$url. " is_hold= ".$is_hold;

        // Cek koneksi ke URL
        $status = checkUrl($url);
      //  echo "<br>status ".$url." = ".$status;

        // Jika berhasil terkoneksi, set is_hold = 1, jika gagal, set is_hold = 0
        $new_is_hold = $status ? 0 : 1;

      //  echo "<br>".$url." new_is_hold= ".$new_is_hold;

        // Perbarui hanya jika status is_hold berubah
        if ($new_is_hold != $is_hold) {
            $hold_date = date('Y-m-d H:i:s'); // Mendapatkan waktu saat ini untuk hold_date

            $update_sql = "UPDATE providers_partners SET is_hold = ?, hold_date = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("isi", $new_is_hold, $hold_date, $id);

           //  echo "<br>".$url." new_is_hold= ".$new_is_hold. " hold_date ".$hold_date;

            if ($stmt->execute()) {
            //    echo "<br>Data provider  `$url` diperbarui. Status is_hold: $new_is_hold, hold_date: $hold_date\n";
            //    echo "<br>";
            } else {
                echo "<br>Gagal memperbarui provider ID $id\n";
            }
            // echo "<br>";
        }
    }
} else {
    echo "<br>Tidak ada data provider.\n";
}

// Tutup koneksi
$conn->close();

?>
