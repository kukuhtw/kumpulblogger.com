<?php
// add_site_internal.php
include("db.php");
include("function.php");
session_start();

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Buat koneksi ke database
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Cek apakah data sudah ada pada table publishers_site dengan internal_blog = 1
$query_site = "SELECT id FROM publishers_site WHERE publishers_local_id = ? AND internal_blog = 1";
$stmt = $mysqli->prepare($query_site);
if (!$stmt) {
    die("Prepare failed (site select): " . $mysqli->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_site = $stmt->get_result();
$exists_in_site = ($result_site->num_rows > 0);
$site_id = $exists_in_site ? $result_site->fetch_assoc()['id'] : null;
$stmt->close();

// Ambil data provider dari file JSON
$this_providers_id = 1;
$this_providers_domain_url = get_providers_domain_url_json("providers_data.json", $this_providers_id);
$this_providers_name = getProvidersNameById_JSON("providers_data.json", $this_providers_id);

// Inisialisasi variabel pesan error/success
$error = "";
$success = "";

// Proses submit form jika belum terdaftar
if (!$exists_in_site && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['site_desc'])) {
    $username    = trim($_POST['username']);
    $description = trim($_POST['site_desc']);
    
    // Validasi username: hanya huruf dan angka, tanpa spasi atau tanda baca
    if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        $error = "Username hanya boleh mengandung huruf dan angka tanpa spasi atau tanda baca.";
    }
    // Validasi minimal deskripsi 10 karakter
    elseif (strlen($description) < 10) {
        $error = "Description minimal 10 characters";
    } else {
        // Cek quota
        $query_quota = "SELECT * FROM publisher_quota WHERE publisher_id = ? OR username = ?";
        $stmt = $mysqli->prepare($query_quota);
        if (!$stmt) {
            die("Prepare failed (quota select): " . $mysqli->error);
        }
        $stmt->bind_param("is", $user_id, $username);
        $stmt->execute();
        $result_quota = $stmt->get_result();
        $exists_in_quota = ($result_quota->num_rows > 0);
        $stmt->close();

        if ($exists_in_quota) {
            $error = "Data quota sudah ada, tidak bisa diinsert.";
        } else {
            // Generate keys
            $public_key = bin2hex(random_bytes(16));
            $secret_key = bin2hex(random_bytes(16));

            $site_name   = $username;
            $site_domain = rtrim($this_providers_domain_url, '/') . "/blog/" . $username;
            
            // Insert site
            $insert_site = "INSERT INTO publishers_site 
                (internal_blog, providers_name, providers_domain_url, publishers_local_id, site_name, site_domain, site_desc, public_key, secret_key, rate_text_ads, advertiser_allowed, advertiser_rejected, regdate, current_site_revenue, current_site_revenue_from_partner, isbanned, banned_date, banned_reason) 
                VALUES 
                (1, ?, ?, ?, ?, ?, ?, ?, ?, 50, '', '', NOW(), 0, 0, 0, NULL, '')";
            $stmt = $mysqli->prepare($insert_site);
            if (!$stmt) {
                die("Prepare failed (site insert): " . $mysqli->error);
            }
            $stmt->bind_param("ssisssss", $this_providers_name, $this_providers_domain_url, $user_id, $site_name, $site_domain, $description, $public_key, $secret_key);
            $stmt->execute();
            $site_id = $mysqli->insert_id;
            $stmt->close();

            // Insert quota
            $insert_quota = "INSERT INTO publisher_quota (publisher_id, pub_id, daily_free_quota, paid_quota, quota_valid_until, username, description, last_updated)
                VALUES (?, ?, 1, 0, NULL, ?, ?, NOW())";
            $stmt = $mysqli->prepare($insert_quota);
            if (!$stmt) {
                die("Prepare failed (quota insert): " . $mysqli->error);
            }
            $stmt->bind_param("iiss", $user_id, $site_id, $username, $description);
            
            //echo "<br>insert_quota : ".$insert_quota;
            //echo "<br>user_id : ".$user_id;
            //echo "<br>site_id : ".$site_id;
            //echo "<br>username : ".$username;
            //echo "<br>description : ".$description;


            //$stmt->execute();
            $stmt->close();

            $success = "Data berhasil ditambahkan.";
            $exists_in_site = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Add New Publisher Site</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
      body { background-color: #f8f9fa; }
      .container { margin-top: 50px; max-width: 900px; }
      .card { box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 8px; padding: 20px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <?php include("main_menu.php"); ?>
      <?php include("include_publisher_menu.php"); ?>
      <h2 class="text-center">Add New Publisher Site</h2>
      
      <?php if($exists_in_site): ?>
        <div class="alert alert-info">
          Data site internal sudah terdaftar.<br>
          <strong>Pub ID:</strong> <?php echo htmlspecialchars($site_id); ?><br>
          <a href="add_article.php?pub_id=<?php echo urlencode($site_id); ?>">Klik untuk submit artikel</a>
        </div>
      <?php else: ?>
        <?php if(!empty($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <?php if(!empty($success)): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <form action="add_site_internal.php" method="POST" id="siteForm">
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" name="username" id="username" class="form-control" pattern="[a-zA-Z0-9]+" title="Hanya huruf dan angka tanpa spasi atau tanda baca" required>
            <div id="usernameFeedback" class="form-text text-danger"></div>
          </div>
          <div class="mb-3">
            <label for="site_desc" class="form-label">Description</label>
            <textarea name="site_desc" id="site_desc" class="form-control" rows="3" required></textarea>
            <div id="descFeedback" class="form-text text-danger"></div>
          </div>
          <button type="submit" class="btn btn-primary" id="submitBtn">Submit</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $(function(){
      function isValidUsername(u){return /^[a-zA-Z0-9]+$/.test(u);}      
      $('#username').blur(function(){var u=$(this).val().trim();if(u){if(!isValidUsername(u)){$('#usernameFeedback').text('Username hanya boleh mengandung huruf dan angka tanpa spasi atau tanda baca.');$('#submitBtn').prop('disabled',true);return;}$.post('check_username.php',{username:u},function(res){if(res.available===false){$('#usernameFeedback').text('Username not available');$('#submitBtn').prop('disabled',true);}else{$('#usernameFeedback').text('');$('#submitBtn').prop('disabled',false);}},'json').fail(function(){$('#usernameFeedback').text('Error checking username');$('#submitBtn').prop('disabled',true);});}});
      $('#site_desc').blur(function(){var d=$(this).val().trim();if(d.length<10){$('#descFeedback').text('Description minimal 10 characters');$('#submitBtn').prop('disabled',true);}else{$('#descFeedback').text('');$('#submitBtn').prop('disabled',false);}});
      $('#siteForm').submit(function(e){var u=$('#username').val().trim(),d=$('#site_desc').val().trim();if(!isValidUsername(u)){e.preventDefault();$('#usernameFeedback').text('Username hanya boleh mengandung huruf dan angka tanpa spasi atau tanda baca.');}if(d.length<10){e.preventDefault();$('#descFeedback').text('Description minimal 10 characters');}});
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
