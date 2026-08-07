<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/public_html/db.php';

$email = trim((string) getenv('ADMIN_EMAIL'));
$password = (string) getenv('ADMIN_PASSWORD');
$whatsapp = trim((string) (getenv('ADMIN_WHATSAPP') ?: '-'));
$realName = trim((string) (getenv('ADMIN_NAME') ?: 'Administrator'));
$allowUpdate = in_array('--update', $argv, true);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "ADMIN_EMAIL harus berupa alamat email yang valid.\n");
    exit(64);
}
if (strlen($password) < 12) {
    fwrite(STDERR, "ADMIN_PASSWORD minimal 12 karakter.\n");
    exit(64);
}

$db = new mysqli($servername_db, $username_db, $password_db, $dbname_db, $port_db);
if ($db->connect_errno) {
    fwrite(STDERR, "Koneksi database gagal: {$db->connect_error}\n");
    exit(1);
}
$db->set_charset('utf8mb4');

$lookup = $db->prepare('SELECT id FROM msadmin WHERE loginemail = ? LIMIT 1');
$lookup->bind_param('s', $email);
$lookup->execute();
$existing = $lookup->get_result()->fetch_assoc();
$lookup->close();

$hash = password_hash($password, PASSWORD_DEFAULT);
if ($hash === false) {
    fwrite(STDERR, "Password tidak dapat di-hash.\n");
    exit(1);
}

if ($existing) {
    if (!$allowUpdate) {
        fwrite(STDERR, "Admin {$email} sudah ada. Gunakan --update untuk mengganti datanya.\n");
        exit(2);
    }
    $statement = $db->prepare(
        'UPDATE msadmin SET passwords = ?, whatsapp = ?, realname = ?, number_last_login_attempt = 0 WHERE id = ?'
    );
    $id = (int) $existing['id'];
    $statement->bind_param('sssi', $hash, $whatsapp, $realName, $id);
    $action = 'diperbarui';
} else {
    $forgotPasswordKey = '';
    $statement = $db->prepare(
        'INSERT INTO msadmin (loginemail, passwords, whatsapp, forgot_password_key, realname) VALUES (?, ?, ?, ?, ?)'
    );
    $statement->bind_param('sssss', $email, $hash, $whatsapp, $forgotPasswordKey, $realName);
    $action = 'dibuat';
}

if (!$statement->execute()) {
    fwrite(STDERR, "Gagal menyimpan admin: {$statement->error}\n");
    exit(1);
}

$statement->close();
$db->close();
fwrite(STDOUT, "Admin {$email} berhasil {$action}.\n");

