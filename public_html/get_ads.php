<?php
/*
get_ads.php

*/
// Koneksi ke database
include("db.php");

$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);

// Periksa koneksi
if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

// Ambil iklan yang sudah dipublikasikan dan belum kadaluarsa
$query = "SELECT id, title_ads, description_ads, landingpage_ads FROM advertisers_ads WHERE ispublished = 1 AND is_expired = 0 ORDER BY published_date DESC LIMIT 10";
$result = $mysqli->query($query);

$ads = [];
if ($result->num_rows > 0) {
    $ads = $result->fetch_all(MYSQLI_ASSOC); // Mengambil semua hasil sebagai array asosiatif
}

header('Content-Type: application/json');
echo json_encode($ads);

$mysqli->close();
?>
