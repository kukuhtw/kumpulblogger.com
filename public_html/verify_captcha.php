<?php
session_start();

$adId = isset($_POST['adId']) ? (int)$_POST['adId'] : 0;
$pubId = isset($_POST['pubId']) ? (int)$_POST['pubId'] : 0;
$localAdsId = isset($_POST['localAdsId']) ? (int)$_POST['localAdsId'] : 0;
$providersDomainUrl = isset($_POST['providersDomainUrl']) ? $_POST['providersDomainUrl'] : '';
$user_answer = isset($_POST['user_answer']) ? (int)$_POST['user_answer'] : 0;
$s = $_SESSION['captcha_result'];

if ($user_answer === $s) {
    $_SESSION['captcha_verified'] = true;
    unset($_SESSION['captcha_result']);  // Hapus captcha_result setelah verifikasi
    header("Location: track_click.php?adId=$adId&pubId=$pubId&localAdsId=$localAdsId&providersDomainUrl=$providersDomainUrl");
    exit();  // Penting untuk menghentikan eksekusi setelah redirect
} else {
    echo "Verifikasi CAPTCHA gagal. Silakan coba lagi.";
}
?>
