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

// ––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––
// 1. Generate CSRF token (jika belum ada)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// ––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. Validasi CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token tidak valid.');
    }

    // 3. Validasi POST id
    if (!isset($_POST['id']) || !ctype_digit($_POST['id'])) {
        exit('ID artikel tidak valid.');
    }
    $article_id   = (int) $_POST['id'];
    $title        = trim($_POST['title'] ?? '');
    $html_content = $_POST['html_content'] ?? '';
    $newTag       = trim($_POST['tag'] ?? '');
    $ispub        = isset($_POST['unpublish']) ? 0 : 1;

    // 4. Validasi judul
    if ($title === '') {
        $error = "Judul artikel wajib diisi.";
    } else {
        // 5. Pastikan artikel milik user
        $check_sql  = "SELECT id FROM articles WHERE id = ? AND publishers_local_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $article_id, $user_id);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();
        if ($check_res->num_rows === 0) {
            $check_stmt->close();
            die("Artikel tidak ditemukan atau Anda tidak memiliki akses.");
        }
        $check_stmt->close();

        // 6. Update artikel
        $upd = $conn->prepare(
            "UPDATE articles
                SET title = ?, html_content = ?, tag = ?, ispublished = ?, updated_at = NOW()
              WHERE id = ? AND publishers_local_id = ?"
        );
        $upd->bind_param(
            "sssiii",
            $title,
            $html_content,
            $newTag,
            $ispub,
            $article_id,
            $user_id
        );
        if ($upd->execute()) {
            $_SESSION['success_message'] = "Artikel berhasil diperbarui.";
            $upd->close();
            header("Location: view_edit_articles.php");
            exit();
        } else {
            $error = "Gagal memperbarui artikel: " . $conn->error;
        }
        $upd->close();
    }
}

// GET: tampilkan form
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    exit('ID artikel tidak valid.');
}
$article_id = (int) $_GET['id'];

$sql   = "SELECT title, html_content, tag, ispublished
            FROM articles
           WHERE id = ? AND publishers_local_id = ?";
$stmt  = $conn->prepare($sql);
$stmt->bind_param("ii", $article_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    exit("Artikel tidak ditemukan atau Anda tidak memiliki akses.");
}
$row           = $result->fetch_assoc();
$title         = $row['title'];
$html_content  = $row['html_content'];
$tags          = $row['tag'];
$ispublished   = $row['ispublished'];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Artikel</title>

  <!-- Bootstrap CSS -->
  <link 
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" 
    rel="stylesheet"
  >

  <style>
    .tox-editor-container { border: 1px solid #ccc; }
  </style>
</head>
<body>
  <nav class="navbar navbar-light bg-light mb-4">
    <div class="container">
      <a class="navbar-brand" href="view_edit_articles.php">← Daftar Artikel</a>
    </div>
  </nav>

  <div class="container">
    <!-- Tampilkan pesan error jika ada -->
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Tampilkan pesan sukses jika ada -->
    <?php if (!empty($_SESSION['success_message'])): ?>
      <div class="alert alert-success">
        <?= htmlspecialchars($_SESSION['success_message']) ?>
        <?php unset($_SESSION['success_message']); ?>
      </div>
    <?php endif; ?>

    <h2 class="mb-4">Edit Artikel</h2>
    <form 
      method="POST" 
      action="edit_article.php?id=<?= $article_id ?>" 
      class="needs-validation" 
      novalidate 
      onsubmit="return syncContent()"
    >
      <!-- Hidden fields: id artikel & csrf_token -->
      <input type="hidden" name="id" value="<?= $article_id ?>">
      <input 
        type="hidden" 
        name="csrf_token" 
        value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
      >

      <div class="mb-3">
        <label for="title" class="form-label">Judul <span class="text-danger">*</span></label>
        <input
          type="text"
          id="title"
          name="title"
          class="form-control"
          required
          maxlength="255"
          value="<?= htmlspecialchars($title) ?>"
        >
        <div class="invalid-feedback">Judul wajib diisi.</div>
      </div>

      <div class="mb-3">
        <label for="tag" class="form-label">Tag (pisahkan dengan koma)</label>
        <input
          type="text"
          name="tag"
          id="tag"
          class="form-control"
          maxlength="500"
          value="<?= htmlspecialchars($tags) ?>"
          placeholder="contoh: teknologi, berita, tutorial"
        >
        <div class="form-text">Maks. 500 karakter</div>
      </div>

      <div class="mb-3">
        <label for="html_content" class="form-label">Konten</label>
        <textarea id="html_content" name="html_content">
<?= htmlspecialchars($html_content) ?>
        </textarea>
      </div>

      <?php if ($ispublished): ?>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" name="unpublish" id="unpublish">
          <label class="form-check-label" for="unpublish">Unpublish artikel</label>
        </div>
      <?php endif; ?>

      <div class="d-flex gap-2 mb-5">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="view_edit_articles.php" class="btn btn-secondary">Batal</a>
      </div>
    </form>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- 1. TinyMCE (pakai API key Anda di URL ini) -->

  <script src="https://cdn.tiny.cloud/1/oi3tf6yka7b3jpt9b383elz1lau8h1lhi3v8r5ank4nelk9g/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>



  <script>
    // 2. Inisialisasi TinyMCE
    tinymce.init({
      selector: '#html_content',
      height: 500,
      menubar: 'file edit view insert format tools table help',
      plugins: 'advlist autolink lists link image charmap preview anchor ' +
               'searchreplace visualblocks code fullscreen ' +
               'insertdatetime media table paste help wordcount',
      toolbar: [
        'undo redo | formatselect | bold italic underline strikethrough | ' +
        'alignleft aligncenter alignright alignjustify | ' +
        'bullist numlist outdent indent | removeformat | ' +
        'link image media table | code'
      ],
      images_upload_url: 'upload_image_article_tm.php',
      automatic_uploads: true,
      image_title: true,
      media_live_embeds: true,
      toolbar_mode: 'sliding',
      content_style: `
        table { width: 100%; border-collapse: collapse; margin-bottom: 1em; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background: #f5f5f5; }
      `
    });

    // 3. Validasi form (Bootstrap 5)
    (function() {
      'use strict';
      document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', event => {
          if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
          }
          form.classList.add('was-validated');
        });
      });
    })();

    // 4. Sinkronkan isi TinyMCE ke <textarea> sebelum form disubmit
    function syncContent() {
      const editor = tinymce.get('html_content');
      if (editor) {
        document.getElementById('html_content').value = editor.getContent();
      }
      return true; // ijinkan submit
    }
  </script>
</body>
</html>
