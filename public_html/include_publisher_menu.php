<style>
    .publisher-menu-panel { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem; padding: 1.25rem; border: 1px solid #e6eaf0; border-radius: 1rem; background: #fff; }
    .publisher-menu-group h2 { margin: 0 0 .85rem; text-align: center; font-size: 1.05rem; font-weight: 700; }
    .publisher-menu-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: .6rem; }
    .publisher-menu-grid .btn { display: inline-flex; flex: 1 1 150px; align-items: center; justify-content: center; gap: .45rem; min-height: 42px; font-size: .88rem; }
    @media (max-width: 991.98px) { .publisher-menu-panel { grid-template-columns: 1fr; } }
    @media (max-width: 575.98px) { .publisher-menu-panel { padding: .9rem; } .publisher-menu-grid .btn { flex: 1 1 calc(50% - .6rem); font-size: .82rem; } }
</style>
<section class="publisher-menu-panel" aria-label="Menu publisher">
    <div class="publisher-menu-group">
        <h2>Menu Publisher</h2>
        <nav class="publisher-menu-grid">
            <a href="view_advertiser_list.php" class="btn btn-info"><i class="fas fa-users"></i> Advertiser Lokal</a>
            <a href="view_advertiser_list_partner.php" class="btn btn-info"><i class="fas fa-handshake"></i> Advertiser Partner</a>
            <a href="view_ads_sort_by_highest_bid_per_click.php" class="btn btn-info"><i class="fas fa-chart-line"></i> Iklan Berjalan</a>
            <a href="add_site.php" class="btn btn-info"><i class="fas fa-plus-circle"></i> Tambah Site</a>
            <a href="mysite.php" class="btn btn-info"><i class="fas fa-list-alt"></i> Site Saya</a>
            <a href="myrevenue.php" class="btn btn-info"><i class="fas fa-dollar-sign"></i> Pendapatan</a>
            <a href="mypayment.php" class="btn btn-info"><i class="fas fa-wallet"></i> Pembayaran</a>
        </nav>
    </div>

    <div class="publisher-menu-group">
        <h2>Blog Internal</h2>
        <nav class="publisher-menu-grid">
            <a href="add_site_internal.php" class="btn btn-info"><i class="fas fa-blog"></i> Buat Blog</a>
            <a href="add_article.php" class="btn btn-info"><i class="fas fa-plus-circle"></i> Buat Artikel</a>
            <a href="view_edit_articles.php" class="btn btn-info"><i class="fas fa-edit"></i> Edit Artikel</a>
            <a href="view_quiz_articles.php" class="btn btn-info"><i class="fas fa-question-circle"></i> Quiz Artikel</a>
            <a href="view_summary_audio_articles.php" class="btn btn-info"><i class="fas fa-volume-up"></i> Summary Audio</a>
            <a href="/view_ai_images_articles.php" class="btn btn-info"><i class="fas fa-images"></i> AI Images</a>
        </nav>
    </div>
</section>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
