<style>
    .advertiser-menu-panel { margin-bottom: 1.25rem; padding: 1.1rem; border: 1px solid #e6eaf0; border-radius: 1rem; background: #fff; }
    .advertiser-menu-panel h2 { margin: 0 0 .85rem; text-align: center; font-size: 1.05rem; font-weight: 700; }
    .advertiser-menu-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: .6rem; }
    .advertiser-menu-grid .btn { display: inline-flex; flex: 1 1 180px; align-items: center; justify-content: center; gap: .45rem; min-height: 42px; font-size: .88rem; }
    @media (max-width: 575.98px) { .advertiser-menu-panel { padding: .9rem; } .advertiser-menu-grid .btn { flex: 1 1 calc(50% - .6rem); font-size: .8rem; } }
</style>
<section class="advertiser-menu-panel" aria-label="Menu advertiser">
    <h2>Menu Advertiser</h2>
    <nav class="advertiser-menu-grid">
        <a href="view_ads.php" class="btn btn-info"><i class="fas fa-bullhorn"></i> Iklan Saya</a>
        <a href="add_advertisement.php" class="btn btn-info"><i class="fas fa-plus-circle"></i> Tambah Iklan</a>
        <a href="view_rate_publisher.php" class="btn btn-info"><i class="fas fa-chart-bar"></i> Rate Publisher Lokal</a>
        <a href="view_rate_publisher_partner.php" class="btn btn-info"><i class="fas fa-handshake"></i> Rate Publisher Partner</a>
    </nav>
</section>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
