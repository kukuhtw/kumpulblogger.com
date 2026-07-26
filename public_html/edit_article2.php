<?php
// edit_article.php
session_start();
include("db.php");
require_once("config.php");

// Database connection
try {
    $db   = new Database($config['database']);
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Gagal koneksi database: " . $e->getMessage());
}

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
//echo "<br>user_id: ".$user_id;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate POST id
    if (!isset($_POST['id']) || !ctype_digit($_POST['id'])) {
        exit('ID artikel tidak valid.');
    }
    $article_id = (int)$_POST['id'];

    // Retrieve inputs
    $title       = $_POST['title'] ?? '';
    $html_content= $_POST['html_content'] ?? '';
     $newTag     = $_POST['tag'];
    $ispub       = isset($_POST['unpublish']) ? 0 : 1;

    // Update article if ownership matches
    /* $upd = $conn->prepare(
        "UPDATE articles
            SET title = ?, html_content = ?, ispublished = ?, updated_at = NOW()
          WHERE id = ? AND publishers_local_id = ?"
    );
    */

   $upd = $conn->prepare(
    "UPDATE articles
        SET title = ?, html_content = ?, tag = ?, ispublished = ?, updated_at = NOW()
      WHERE id = ? AND publishers_local_id = ?"
  );

    // $upd->bind_param("ssiii", $title, $html_content, $ispub, $article_id, $user_id);

     $upd->bind_param(
    "sssiii",
    $title,
    $html_content,
    $newTag,
    $ispub,
    $article_id,
    $user_id
  );
    $upd->execute();
    $upd->close();

    // Redirect back to list
    header("Location: view_edit_articles.php");
    exit();
}

// GET: display form
// Validate GET id
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    exit('ID artikel tidak valid.');
}

$article_id = (int)$_GET['id'];
//echo "<br>article_id: ".$article_id;


// 1. Prepare dan bind
$sql = "SELECT title, html_content, tag, ispublished
        FROM articles
        WHERE id = ? AND publishers_local_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $article_id, $user_id);

// 2. Execute dan buffer hasil
$stmt->execute();
$stmt->store_result();

// 3. Cek ada tidaknya baris
if ($stmt->num_rows === 0) {
    $stmt->close();
    exit("Artikel tidak ditemukan atau Anda tidak memiliki akses.");
}

// 4. Bind kolom ke variabel, lalu fetch
$stmt->bind_result($title, $html_content, $tags, $ispublished);
$stmt->fetch();
$stmt->close();

// 5. Tampilkan hasil (setelah fetch)
//echo "<br>article_id: " . $article_id;
//echo "<br>title      : " . htmlspecialchars($title);
//echo "<br>tags       : " . htmlspecialchars($tags);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Artikel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

</head>
<body>
<nav class="navbar navbar-light bg-light mb-4">
  <div class="container">
    <a class="navbar-brand" href="view_edit_articles.php">← Daftar Artikel</a>
  </div>
</nav>

<div class="container">

      <?php include("main_menu.php"); ?>
      <?php include("include_publisher_menu.php"); ?>
  <h2 class="mb-4">Edit Artikel</h2>
  <form method="POST" action="edit_article.php?id=<?= $article_id ?>" class="needs-validation" novalidate onsubmit="syncContent()">
    <input type="hidden" name="id" value="<?= $article_id ?>">
    <input type="hidden" id="articleId" value="<?= $article_id ?>">

    <div class="mb-3">
      <label for="title" class="form-label">Judul</label>
      <input type="text" id="title" name="title" class="form-control" required value="<?= htmlspecialchars($title) ?>">
      <div class="invalid-feedback">Judul wajib diisi.</div>
    </div>

     <!-- Tambahan untuk tag -->
  <div class="mb-3">
    <label for="tag" class="form-label">Tag (pisahkan dengan koma)</label>
    <input type="text" name="tag" id="tag"
           class="form-control"
           value="<?= htmlspecialchars($tags) ?>">
  </div>




    <div class="mb-3">
      <label class="form-label">Konten</label>
      <div id="editor" style="height: 400px; background: #fff;"></div>
      <input type="hidden" name="html_content" id="html_content">
    </div>


    <?php if ($ispublished): ?>
    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" name="unpublish" id="unpublish">
      <label class="form-check-label" for="unpublish">Unpublish artikel</label>
    </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="view_edit_articles.php" class="btn btn-secondary ms-2">Batal</a>
  </form>
</div>

<!-- Modal untuk edit full HTML -->
<!-- Modal untuk Edit Full HTML -->
<div class="modal fade" id="htmlModal" tabindex="-1" aria-labelledby="htmlModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="htmlModalLabel">Edit HTML Konten</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <textarea id="htmlModalTextarea" class="form-control" style="height: 400px;"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="htmlModalSave">Simpan HTML</button>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>


<script>
(function() {
  'use strict';
  document.querySelectorAll('.needs-validation').forEach(form => {
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {
        event.preventDefault(); event.stopPropagation();
      }
      form.classList.add('was-validated');
    });
  });
})();


 var quill = new Quill('#editor', {
      theme: 'snow',
      modules: {
        toolbar: {
          container: [
            ['bold','italic','underline'],
            [{ 'header': [1,2,false] }],
            ['link','image','video','html'],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }]
          ],
          handlers: {
            image: imageHandler,
            video: videoHandler,
            html: htmlHandler
          }
        }
      }
    });

quill.clipboard.dangerouslyPasteHTML(0, <?= json_encode($html_content) ?>);

// Handler video untuk YouTube, Instagram & TikTok
 

function imageHandler() {
  const input = document.createElement('input');
  input.type = 'file'; input.accept = 'image/*';
  input.click();
  input.onchange = () => {
    const file = input.files[0];
    const formData = new FormData();
    formData.append('image', file);
    formData.append('article_id', document.getElementById('articleId').value);
    fetch('upload_image_article.php', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        if (data.url) {
          const range = quill.getSelection(true);
          quill.insertEmbed(range.index, 'image', data.url);
          quill.setSelection(range.index + 1);
        } else console.error('Upload error:', data.error);
      });
  };
}

 function videoHandler() {
      const url = prompt('Masukkan URL video YouTube, Instagram, atau TikTok:');
      if (!url) return;
      let embedHTML;

      if (/youtube\.com\/watch\?v=|youtu\.be\//.test(url)) {
        // YouTube
        let videoId = url.includes('youtu.be/')
          ? url.split('youtu.be/')[1].split(/[?&]/)[0]
          : new URL(url).searchParams.get('v');
        embedHTML = `<iframe width="560" height="315"
                          src="https://www.youtube.com/embed/${videoId}"
                          frameborder="0" allowfullscreen></iframe>`;
      }
      else if (url.includes('instagram.com')) {
        // Instagram blockquote saja—script sudah di-head
        embedHTML = `<blockquote class="instagram-media" 
                                  data-instgrm-permalink="${url}"
                                  data-instgrm-version="14"></blockquote>`;
      }
      else if (url.includes('tiktok.com')) {
        // TikTok blockquote saja—script sudah di-head
        const videoId = url.split('/video/')[1]?.split('?')[0] || '';
        embedHTML = `<blockquote class="tiktok-embed" cite="${url}" data-video-id="${videoId}">
                       <a href="${url}"></a>
                     </blockquote>`;
      }
      else {
        // Fallback HTML5 video
        embedHTML = `<video controls src="${url}" style="max-width:100%;"></video>`;
      }

      // Sisipkan ke editor
      const range = quill.getSelection(true);
      quill.clipboard.dangerouslyPasteHTML(range.index, embedHTML);
      quill.setSelection(range.index + 1);

      // Re-trigger embed.js (hanya untuk IG & TikTok)
      if (url.includes('instagram.com') && window.instgrm && instgrm.Embeds) {
        instgrm.Embeds.process();
      }
      if (url.includes('tiktok.com')) {
        // append ulang skrip untuk mem-parse embed baru
        const s = document.createElement('script');
        s.src = "https://www.tiktok.com/embed.js";
        s.async = true;
        document.body.appendChild(s);
      }
    }

  
// Handler untuk edit full HTML via modal + nl2br
function htmlHandler() {
  // Isi textarea dengan konten Quill sekarang
  document.getElementById('htmlModalTextarea').value = quill.root.innerHTML;
  // Tampilkan modal
  const htmlModal = new bootstrap.Modal(document.getElementById('htmlModal'));
  htmlModal.show();

  // Simpan perubahan saat tombol diklik
  document.getElementById('htmlModalSave').onclick = () => {
    let updated = document.getElementById('htmlModalTextarea').value;
    // nl2br: ganti newline jadi <br>
    updated = updated.replace(/\r?\n/g, '<br>');
    quill.root.innerHTML = updated;
    htmlModal.hide();
  };
}

  


function syncContent() {
  document.getElementById('html_content').value = quill.root.innerHTML;
}
</script>
</body>
</html>