<?php
$user_email = $_SESSION['email'] ?? '';
?>
<style>
    .app-main-menu { margin-bottom: 1.25rem; padding: 1.25rem; border: 1px solid #e6eaf0; border-radius: 1rem; background: #fff; text-align: center; }
    .app-main-menu__welcome { display: flex; flex-wrap: wrap; justify-content: center; gap: .35rem; margin-bottom: .5rem; }
    .app-main-menu__whatsapp { display: inline-block; margin-bottom: 1rem; text-decoration: none; }
    .app-menu-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: .6rem; }
    .app-menu-actions .btn { display: inline-flex; align-items: center; justify-content: center; gap: .45rem; min-height: 42px; }
    @media (max-width: 575.98px) { .app-main-menu { padding: .9rem; } .app-menu-actions .btn { flex: 1 1 calc(50% - .6rem); font-size: .82rem; } }
</style>
<section class="app-main-menu" aria-label="Menu utama">
    <div class="app-main-menu__welcome">
        <span>Selamat datang,</span>
        <strong><?php echo htmlspecialchars($user_email); ?></strong>
    </div>
    <a class="app-main-menu__whatsapp" target="_blank" rel="noopener noreferrer" href="https://chat.whatsapp.com/FfKleajKG7fKlHhRH1UZca">
        <i class="fab fa-whatsapp"></i> Gabung grup WhatsApp untuk mendapatkan informasi terbaru
    </a>
    <nav class="app-menu-actions">
        <a href="profile.php" class="btn btn-info"><i class="fas fa-user"></i> Profil</a>
        <a href="advertiser_menu.php" class="btn btn-info"><i class="fas fa-bullhorn"></i> Advertiser</a>
        <a href="publisher_menu.php" class="btn btn-info"><i class="fas fa-globe"></i> Publisher</a>
        <a href="settings.php" class="btn btn-warning"><i class="fas fa-cog"></i> Pengaturan</a>
        <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</section>
