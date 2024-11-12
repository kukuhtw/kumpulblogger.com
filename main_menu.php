
<?php
// Get user email from session
$user_email = $_SESSION['email'];
?>
<p class="text-center">Selamat datang, <strong><?php echo $user_email; ?></strong>!</p>
<p class="text-center"><a target="_blank" href="https://chat.whatsapp.com/FfKleajKG7fKlHhRH1UZca">Gabung whatsapp group <br> untuk mendapatkan latest info</a></p>

 <div class="text-center mt-4">
            <a href="profile.php" class="btn btn-info">Lihat Profil</a>
             <a href="advertiser_menu.php" class="btn btn-info">Advertiser</a>
             <a href="publisher_menu.php" class="btn btn-info">Publisher</a>
             
            <a href="settings.php" class="btn btn-warning">Pengaturan Akun</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
