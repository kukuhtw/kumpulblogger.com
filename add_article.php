<?php
// add_article.php - Frontend user interface for article creation
session_start();
include("db.php");

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Include provider data functions
include_once("function.php");


// Buat koneksi ke database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}



// Buat koneksi ke database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}


// Ambil username dari publisher_quota
$username = '';
if ($stmt = $mysqli->prepare("SELECT username FROM publisher_quota WHERE publisher_id = ?")) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($username);
    $stmt->fetch();
    $stmt->close();
}
// pastikan kalau null jadi string kosong
$username = $username ?: '';

// Get provider data
$this_providers_id = 1;
$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", $this_providers_id);
$this_providers_name = getProvidersNameById_JSON("providers_data.json", $this_providers_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entry Artikel - Publisher</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CKEditor CDN -->
   
    <script src="https://cdn.ckeditor.com/4.25.1-lts/full/ckeditor.js"></script>
    
    <script>
    // Variabel JS yang kita pakai untuk redirect
    const publisherUsername = '<?php echo htmlspecialchars($username, ENT_QUOTES); ?>';
  </script>

    <!-- Sweet Alert for notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f8f9fa; }
        .container { margin-top: 50px; max-width: 900px; }
        .card { box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 8px; padding: 20px; }
        .form-label { font-weight: 500; }
        .alert { margin-top: 20px; }
        .progress-container { 
            margin: 20px 0;
            display: none;
        }
        #statusMessage {
            margin-top: 10px;
            font-style: italic;
        }
        .token-info {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <?php include("main_menu.php"); ?>
            <?php include("include_publisher_menu.php"); ?>
            
            <h2 class="text-center mb-4">Entry Artikel dengan GPT 4</h2>

             <a
  href="blog/<?php echo $username; ?>"
  target="_blank"
  id="viewArticleBtn"
  class="btn btn-success"
>
  View Article
</a>
            
            <div id="alertContainer"></div>
            
            <!-- Progress indicator -->
            <div class="progress-container" id="progressContainer">
                <div class="progress">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                </div>
                <div id="statusMessage">Memproses...</div>
            </div>
            
            <!-- Quota information -->
            <div class="alert alert-info" id="quotaInfo">
                <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                Memuat informasi quota...
            </div>

            <!-- Initial Form -->
            <div id="initialFormContainer">
                <form id="initialForm">
                    <input type="hidden" name="action" value="generate_article">
                    
<!-- Tambahkan di dalam <form id="initialForm">, sebelum tombol Generate -->
<div class="mb-3">
    <button type="button" class="btn btn-outline-primary" id="getIdeaBtn">Get an Idea</button>
</div>

                    
                    <div class="mb-3">
                        <label for="topic" class="form-label">Topik Artikel</label>
                        <input type="text" class="form-control" id="topic" name="topic" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="raw_content" class="form-label">Raw Content</label>
                        <textarea class="form-control" id="raw_content" name="raw_content" rows="5" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tone" class="form-label">Tone Artikel</label>
                        <select class="form-control" id="tone" name="tone">
                            <option value="formal">Formal</option>
                            <option value="casual">Casual</option>
                            <option value="gaya gaul">Gaya Gaul</option>
                            <option value="Nyinyir Sinis">Nyinyir Sinis</option>
                            <option value="Gaya Campuran Inggris Indonesia">Gaya Campuran Inggris Indonesia</option>
                            <option value="Ceria">Ceria</option>
                            <option value="persuasive">Persuasif</option>
                            <option value="informative">Informatif</option>
                            <option value="satire">Satire</option>
                            <option value="soft marketing">Soft Marketing</option>
                            <option value="hardcore sell">Hardcore Sell</option>
                            <option value="tutorial">Tutorial</option>
                            <option value="investigation">Investigation</option>
                            <option value="pessimisme">Pessimisme</option>
                            <option value="judgement">Judgement</option>
                            <option value="sentimental">Sentimental</option>
                            <option value="story telling">Story Telling</option>
                            <option value="Jurnalistik">Jurnalistik</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="language" class="form-label">Bahasa Artikel</label>
                        <select class="form-control" id="language" name="language">
                            <option value="Indonesia">Indonesia</option>
                            <option value="Jawa">Jawa</option>
                            <option value="Betawi">Betawi</option>
                            <option value="Sunda">Sunda</option>
                            <option value="Minang">Minang</option>
                            <option value="Batak">Batak</option>
                            <option value="Makassar">Makassar</option>
                            <option value="Papua">Papua</option>
                            <option value="Aceh">Aceh</option>
                            <option value="English">English</option>
                        </select>
                    </div>

                   <?php if (!empty($username)) : ?>
    <!-- Jika username ada, tampilkan tombol submit -->
    <button type="submit" id="generateBtn" class="btn btn-primary">Generate Artikel</button>
<?php else : ?>
    <!-- Jika username kosong, tampilkan hyperlink -->
    <a href="add_site_internal.php" class="btn btn-warning">Lengkapi Data Site Internal</a>
<?php endif; ?>

                    
                   


                </form>
            </div>

            <!-- Editor Form (hidden initially) -->
            <div id="editorFormContainer" style="display: none;">
                <form id="publishForm">
                    <input type="hidden" name="action" value="publish_article">
                    <input type="hidden" id="pub_id" name="pub_id" value="">
                    <input type="hidden" id="topic_hidden" name="topic" value="">
                    <input type="hidden" id="tone_hidden" name="tone" value="">
                    <input type="hidden" id="language_hidden" name="language" value="">
                    <input type="hidden" id="pub_id" name="pub_id" value="">
                    <input type="hidden" id="tag_hidden" name="tag" value="">  <!-- New hidden field for tag -->

                    <input type="hidden" id="input_token" name="input_token" value="0">
                    <input type="hidden" id="output_token" name="output_token" value="0">
                    <input type="hidden" id="json_response" name="json_response" value="">

                
    <input type="hidden" id="slug_hidden" name="slug"     value="">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Judul Artikel</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editor" class="form-label">Konten Artikel (HTML)</label>
                        <textarea class="form-control" id="editor" name="html_content" rows="10" required></textarea>
                    </div>
                    
                    <div class="token-info">
                        Input Tokens: <span id="inputTokenDisplay">0</span> | 
                        Output Tokens: <span id="outputTokenDisplay">0</span>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" id="backBtn" class="btn btn-secondary">Kembali</button>
                      
                        <!-- Tombol baru di form publish: -->
                     <a
  href="blog/<?php echo $username; ?>"
  target="_blank"
  id="viewArticleBtn"
  class="btn btn-success"
>
  View Article
</a>


                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize CKEditor when editor form is shown
        let editor;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Check quota on page load
            checkQuota();
            
            // Initial form submission
            document.getElementById('initialForm').addEventListener('submit', function(e) {
                e.preventDefault();
                generateArticle();
            });
            
            // Publish form submission
            document.getElementById('publishForm').addEventListener('submit', function(e) {
                e.preventDefault();
                publishArticle();
            });
            
            // Back button handler
            document.getElementById('backBtn').addEventListener('click', function() {
                showInitialForm();
            });
        });
        
        // Check user quota
        function checkQuota() {
            showProgress('Memeriksa quota...');
            
            fetch('article_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'check_quota'
                })
            })
            .then(response => response.json())
            .then(data => {
                hideProgress();
                
                if (data.status === 'success') {
                    const quotaInfo = document.getElementById('quotaInfo');
                    const remainingQuota = data.data.remaining_quota;
                    
                    quotaInfo.innerHTML = `
                        <strong>Info Quota:</strong> 
                        ${data.data.used_quota} dari ${data.data.total_quota} artikel d                    Sisa quota: ${remainingQuota} artikel.
                    `;
                    
                    if (remainingQuota <= 0) {
                        quotaInfo.classList.remove('alert-info');
                        quotaInfo.classList.add('alert-danger');
                        document.getElementById('generateBtn').disabled = true;
                        showAlert('warning', 'Quota harian telah tercapai. Anda tidak dapat membuat artikel baru hari ini.');
                    } else {
                        quotaInfo.classList.remove('alert-danger');
                        quotaInfo.classList.add('alert-info');
                        document.getElementById('generateBtn').disabled = false;
                    }
                } else {
                    showAlert('danger', 'Gagal memeriksa quota: ' + data.message);
                }
            })
            .catch(error => {
                hideProgress();
                showAlert('danger', 'Error: ' + error.message);
            });
        }
        
        // Generate article
        function generateArticle() {
             const btn = document.getElementById('generateBtn');
            // kalau sudah disable, hentikan (opsional)
            if (btn.disabled) return;
            btn.disabled = true;
            btn.textContent = 'Memproses…';  // feedback teks

            showProgress('Generating article...');
            
            const formData = new FormData(document.getElementById('initialForm'));
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            
            fetch('article_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                hideProgress();
                
                if (data.status === 'success') {
                    // Save data to hidden fields
                    document.getElementById('pub_id').value = data.data.pub_id;
                    document.getElementById('slug_hidden').value = data.data.slug;   
                    document.getElementById('topic_hidden').value = data.data.topic;
                    document.getElementById('tone_hidden').value = data.data.tone;
                    document.getElementById('language_hidden').value = data.data.language;
                    document.getElementById('input_token').value = data.data.input_token;
                    document.getElementById('output_token').value = data.data.output_token;
                    document.getElementById('json_response').value = data.data.json_response;
                    
                    // Display token usage
                    document.getElementById('inputTokenDisplay').textContent = data.data.input_token;
                    document.getElementById('outputTokenDisplay').textContent = data.data.output_token;
                    
                      // Set title and content in form
                    document.getElementById('title').value = data.data.title;
                    document.getElementById('editor').value = data.data.content;
                    // Set the tag hidden field
document.getElementById('tag_hidden').value = data.data.tag;

                    
                    // Initialize CKEditor
                    if (typeof CKEDITOR !== 'undefined') {
                        if (editor) {
                            editor.destroy();
                        }
                        editor = CKEDITOR.replace('editor');
                         editor.setData(data.data.html_content); // set html_content value
                    }
                    
                    // Switch to editor form
                    showEditorForm();
                    
                    // Update quota display after successful generation
                    checkQuota();
                } else {
                    showAlert('danger', 'Gagal membuat artikel: ' + data.message);
                }
            })
            .catch(error => {
                 btn.disabled = false;
                btn.textContent = 'Generate Artikel';
                hideProgress();
                showAlert('danger', 'Error: ' + error.message);
            });
        }
        
        // Publish article
        function publishArticle() {
  const pubId = document.getElementById('pub_id').value;
  const slug  = encodeURIComponent(document.getElementById('slug_hidden').value);

  if (!pubId || !slug) {
    Swal.fire({
      icon: 'error',
      title: 'Oops...',
      text: 'ID atau slug artikel belum tersedia!'
    });
    return;
  }

  // Redirect langsung ke URL detail artikel
  // Misal rutenya /blog/{username}/{id}/{slug}
  
  window.location.href = `/blog/${publisherUsername}/${pubId}/${slug}`
}

       

       
   

        
        // UI Helper Functions
        function showEditorForm() {
            document.getElementById('initialFormContainer').style.display = 'none';
            document.getElementById('editorFormContainer').style.display = 'block';
        }
        
        function showInitialForm() {
            document.getElementById('editorFormContainer').style.display = 'none';
            document.getElementById('initialFormContainer').style.display = 'block';
        }
        
        function showProgress(message) {
            const progressContainer = document.getElementById('progressContainer');
            const statusMessage = document.getElementById('statusMessage');
            
            progressContainer.style.display = 'block';
            statusMessage.textContent = message || 'Memproses...';
            
            // Start progress animation
            let progress = 0;
            const progressBar = document.getElementById('progressBar');
            
            const interval = setInterval(function() {
                if (progress >= 90) {
                    clearInterval(interval);
                } else {
                    progress += 5;
                    progressBar.style.width = progress + '%';
                }
            }, 300);
            
            // Store interval ID to clear later
            window.progressInterval = interval;
        }
        
        function hideProgress() {
            const progressContainer = document.getElementById('progressContainer');
            progressContainer.style.display = 'none';
            
            // Reset progress bar
            const progressBar = document.getElementById('progressBar');
            progressBar.style.width = '0%';
            
            // Clear interval if exists
            if (window.progressInterval) {
                clearInterval(window.progressInterval);
            }
        }
        
        function showAlert(type, message) {
            const alertContainer = document.getElementById('alertContainer');
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            // Auto-hide alerts after 5 seconds
            setTimeout(() => {
                const alert = alertContainer.querySelector('.alert');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000000);
        }
    </script>

    <script>
document.addEventListener('DOMContentLoaded', () => {
  const getIdeaBtn = document.getElementById('getIdeaBtn');
  const ideaModalEl = document.getElementById('ideaModal');
  const ideaList = document.getElementById('ideaList');
  const ideaModal = new bootstrap.Modal(ideaModalEl);

  getIdeaBtn.addEventListener('click', () => {
    ideaModal.show();
    ideaList.innerHTML = '<li class="list-group-item">Memuat ide...</li>';
    fetch('get_ideas.php')
      .then(res => res.json())
      .then(data => {
        ideaList.innerHTML = '';
        data.forEach(item => {
          const li = document.createElement('li');
          li.className = 'list-group-item list-group-item-action';
          li.textContent = item.topik;
          li.dataset.deskripsi = item.deskripsi;
          li.addEventListener('click', () => {
            document.getElementById('topic').value = item.topik;
            document.getElementById('raw_content').value = item.deskripsi;
            ideaModal.hide();
          });
          ideaList.appendChild(li);
        });
      })
      .catch(err => {
        ideaList.innerHTML = '<li class="list-group-item text-danger">Gagal memuat ide.</li>';
        console.error(err);
      });
  });
});
</script>

<!-- Modal untuk menampilkan ide -->
<div class="modal fade" id="ideaModal" tabindex="-1" aria-labelledby="ideaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="ideaModalLabel">Pilih Ide Artikel</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <ul class="list-group" id="ideaList">
          <li class="list-group-item">Memuat ide...</li>
        </ul>
      </div>
    </div>
  </div>
</div>
</body>
</html>