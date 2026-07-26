<?php
// admin/llm_settings.php
session_start();
include("../db.php");
include("function_admin.php");

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}

$loginemail_admin = $_SESSION['loginemail_admin'];

// Database connection using MySQLi
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit("Database connection failed.");
}

// article_api.php, generate_ai_images.php, generate_audio_summary.php, dan
// generate_quiz.php semuanya membaca konfigurasi lewat
// "SELECT * FROM llm_settings ORDER BY id LIMIT 1" — jadi tabel ini dipakai
// sebagai SATU baris config aktif, bukan daftar multi-baris. Halaman ini
// meng-upsert baris pertama itu (bukan menambah baris baru tiap disimpan).
function getLlmSettingsRow($mysqli) {
    $result = $mysqli->query("SELECT id, llm_model, openai_key, replicate_key, max_tokens, temperature FROM llm_settings ORDER BY id ASC LIMIT 1");
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
}

$llm_settings_error = '';
$llm_settings_success = '';

if (isset($_GET['saved'])) {
    $llm_settings_success = 'Konfigurasi LLM berhasil disimpan.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_csrf_valid()) {
        $llm_settings_error = 'Permintaan tidak valid (CSRF token tidak cocok). Silakan muat ulang halaman dan coba lagi.';
        $existing = getLlmSettingsRow($mysqli);
        $llm_model = $existing['llm_model'] ?? '';
        $openai_key = $existing['openai_key'] ?? '';
        $replicate_key = $existing['replicate_key'] ?? '';
        $max_tokens = $existing['max_tokens'] ?? 2048;
        $temperature = $existing['temperature'] ?? 0.70;
    } else {
        $llm_model = trim($_POST['llm_model'] ?? '');
        $openai_key = trim($_POST['openai_key'] ?? '');
        $replicate_key = trim($_POST['replicate_key'] ?? '');
        $max_tokens = filter_input(INPUT_POST, 'max_tokens', FILTER_VALIDATE_INT);
        $temperature = filter_input(INPUT_POST, 'temperature', FILTER_VALIDATE_FLOAT);

        if ($llm_model === '') {
            $llm_settings_error = 'Nama model tidak boleh kosong.';
        } elseif ($max_tokens === false || $max_tokens <= 0) {
            $llm_settings_error = 'Max tokens harus berupa angka bulat lebih dari 0.';
        } elseif ($temperature === false || $temperature < 0 || $temperature > 2) {
            $llm_settings_error = 'Temperature harus berupa angka antara 0 dan 2.';
        } else {
            $existing = getLlmSettingsRow($mysqli);
            if ($existing) {
                $existing_id = (int) $existing['id'];
                $stmt = $mysqli->prepare("UPDATE llm_settings SET llm_model = ?, openai_key = ?, replicate_key = ?, max_tokens = ?, temperature = ? WHERE id = ?");
                $stmt->bind_param("sssidi", $llm_model, $openai_key, $replicate_key, $max_tokens, $temperature, $existing_id);
            } else {
                $stmt = $mysqli->prepare("INSERT INTO llm_settings (llm_model, openai_key, replicate_key, max_tokens, temperature, regdate) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("sssid", $llm_model, $openai_key, $replicate_key, $max_tokens, $temperature);
            }

            if ($stmt->execute()) {
                $stmt->close();
                $mysqli->close();
                header("Location: llm_settings.php?saved=1");
                exit;
            }

            $llm_settings_error = 'Gagal menyimpan konfigurasi: ' . $stmt->error;
            $stmt->close();
        }
    }
} else {
    $existing = getLlmSettingsRow($mysqli);
    $llm_model = $existing['llm_model'] ?? '';
    $openai_key = $existing['openai_key'] ?? '';
    $replicate_key = $existing['replicate_key'] ?? '';
    $max_tokens = $existing['max_tokens'] ?? 2048;
    $temperature = $existing['temperature'] ?? 0.70;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LLM Settings</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include("style_toogle.php") ?>
    <style>
        body {
            background-color: #f8f9fa;
            position: relative;
            min-height: 100vh;
        }
        .card {
            margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #28a745;
            color: white;
            font-size: 24px;
            text-align: center;
        }
        .input-group .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        .footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px;
            position: absolute;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>

<?php include("sidebar_menu.php"); ?>

<div class="container" id="mainContent">
    <div class="card">
        <div class="card-header">LLM Settings</div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Fungsi halaman ini:</strong> mengatur model AI dan API key yang dipakai fitur konten otomatis
                (generate artikel, gambar AI, ringkasan audio, dan kuis) di <code>article_api.php</code>,
                <code>generate_ai_images.php</code>, <code>generate_audio_summary.php</code>, dan <code>generate_quiz.php</code>.
                Hanya ada <strong>satu konfigurasi aktif</strong> yang dipakai bersama oleh semua fitur di atas — menyimpan
                form ini akan memperbarui konfigurasi tersebut, bukan menambah konfigurasi baru.
            </div>

            <?php if (!empty($llm_settings_error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($llm_settings_error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($llm_settings_success)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($llm_settings_success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?php echo admin_csrf_field(); ?>

                <div class="mb-3">
                    <label for="llm_model" class="form-label">Nama Model</label>
                    <input type="text" class="form-control" id="llm_model" name="llm_model"
                           value="<?php echo htmlspecialchars($llm_model); ?>"
                           placeholder="mis. gpt-4o-mini" required>
                    <div class="form-text">Nama model yang dikirim ke API (OpenAI atau kompatibel).</div>
                </div>

                <div class="mb-3">
                    <label for="openai_key" class="form-label">OpenAI API Key</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="openai_key" name="openai_key"
                               value="<?php echo htmlspecialchars($openai_key); ?>" autocomplete="off">
                        <button class="btn btn-outline-secondary toggle-key" type="button" data-target="openai_key">Show</button>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="replicate_key" class="form-label">Replicate API Key</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="replicate_key" name="replicate_key"
                               value="<?php echo htmlspecialchars($replicate_key); ?>" autocomplete="off">
                        <button class="btn btn-outline-secondary toggle-key" type="button" data-target="replicate_key">Show</button>
                    </div>
                    <div class="form-text">Dipakai oleh fitur generate gambar AI (model di luar OpenAI).</div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="max_tokens" class="form-label">Max Tokens</label>
                        <input type="number" class="form-control" id="max_tokens" name="max_tokens" min="1" step="1"
                               value="<?php echo htmlspecialchars((string) $max_tokens); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="temperature" class="form-label">Temperature</label>
                        <input type="number" class="form-control" id="temperature" name="temperature" min="0" max="2" step="0.01"
                               value="<?php echo htmlspecialchars((string) $temperature); ?>" required>
                        <div class="form-text">0 = paling konsisten/kaku, 2 = paling acak/kreatif. Default: 0.70.</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Simpan Konfigurasi</button>
            </form>
        </div>
    </div>
</div>

<?php
$mysqli->close();
include("footer.php");
?>

<?php include("js_toogle.php"); ?>

<script>
    document.querySelectorAll('.toggle-key').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-target'));
            var isHidden = input.getAttribute('type') === 'password';
            input.setAttribute('type', isHidden ? 'text' : 'password');
            btn.textContent = isHidden ? 'Hide' : 'Show';
        });
    });
</script>

</body>
</html>
