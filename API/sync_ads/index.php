<?php
// {BASE_END_POINT}API/sync_ads/index.php

include("../../db.php");
include("../../function.php");
ini_set("error_log", "errr_.txt");
$json = file_get_contents('php://input');
$data = json_decode($json, true);


if (isset($data) && isset($data['providers_domain_url'])) {
    $providers_domain_url = $data['providers_domain_url'];
} else {
    // Handle the case where $data is null or 'providers_domain_url' does not exist
    $providers_domain_url = null; // or some default value
    // Optionally, you can log an error or throw an exception if this is an unexpected condition
}
    

  // Database connection using PDO for secure database interaction
try {
    $pdo = new PDO("mysql:host=$servername_db;dbname=$dbname_db;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    exit("Database connection failed.");
}


// Database connection using MySQLi
$conn = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit("Database connection failed.");
}



// Extract headers
$headers = getallheaders();
$Header_public_key = isset($headers['public_key']) ? $headers['public_key'] : null;
$Header_secret_key = isset($headers['secret_key']) ? $headers['secret_key'] : null;

// Check if the required headers are present
if (!$Header_public_key || !$Header_secret_key) {
    $response = array(
        'status' => 'error',
        'message' => 'Missing required headers.'
    );
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}


if (checkProviderCredentials($providers_domain_url, $Header_public_key, $Header_secret_key, $pdo)) {
    //echo "Credentials are valid!";
} else {
    $response = array(
        'status' => 'error',
        'message' => 'Invalid public:or secret key'
    );
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

$data_title_ads = $data['title_ads'];
//debug_text('tra68.txt',$data_title_ads);

if (isset($data['title_ads']) && isset($data_title_ads)) {

    $providers_name = $data['providers_name'];
    $providers_domain_url = $data['providers_domain_url'];
    $advertisers_id = $data['advertisers_id'];
    $local_ads_id = $data['local_ads_id'];
    $ispublished = $data['ispublished']; 
    
    $title_ads = $data['title_ads'];

    $description_ads = $data['description_ads'];
    $landingpage_ads = $data['landingpage_ads'];
    $image_url= $data['image_url'];

     $total_click = $data['total_click'];
      $current_click = $data['current_click'];
      $budget_per_click_textads  = $data['budget_per_click_textads'];

      $is_expired  = $data['is_expired'];
    $expired_date  = $data['expired_date'];
    $is_paused  = $data['is_paused'];
    $paused_date  = $data['paused_date'];

    $budget_allocation = $data['budget_allocation'];
 $current_spending = $data['current_spending'];

    $expected_secret_key = sha1($title_ads . $description_ads .$landingpage_ads.$providers_domain_url);

    if (true) {
        // Process the request 

        $rt = insertOrUpdateAdvertisersAdsPartner($pdo, $local_ads_id, $providers_name, $providers_domain_url, $advertisers_id, $title_ads, $description_ads, 
            $landingpage_ads, $image_url, 
            $ispublished, $total_click, $current_click,$budget_per_click_textads, $is_expired,  $expired_date ,     $is_paused , $paused_date ,   $budget_allocation, $current_spending);

       
        $response = array(
                    'status' => 'success',
                    'message' => $rt 
                );

    }

}
else {
    // Missing required data
    $response = array(
        'status' => 'error',
        'message' => 'Invalid request. Missing required data.'
    );
}


// Send response as JSON
header('Content-Type: application/json');
echo json_encode($response);




function insertOrUpdateAdvertisersAdsPartner($pdo, $local_ads_id, $providers_name, $providers_domain_url, $advertisers_id, $title_ads, $description_ads, $landingpage_ads, $image_url,
    $ispublished, $total_click, $current_click, $budget_per_click_textads, $is_expired,  $expired_date ,     $is_paused , $paused_date,   $budget_allocation ,$current_spending ) {
    // Set timezone to GMT+7
    
    $return = "";
   // debug_text('insertOrUpdateAdvertisersAdsPartner_108.txt',$providers_domain_url);

    date_default_timezone_set('Asia/Jakarta');
    $regdate = date('Y-m-d H:i:s'); // Current date and time in GMT+7

    // Check if the entry already exists
    $sqlCheck = "SELECT id FROM advertisers_ads_partners WHERE local_ads_id = :local_ads_id AND providers_domain_url = :providers_domain_url";
     
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->bindParam(':local_ads_id', $local_ads_id, PDO::PARAM_INT);
    $stmtCheck->bindParam(':providers_domain_url', $providers_domain_url, PDO::PARAM_STR);
    $stmtCheck->execute();
    $existingEntry = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    

    if ($existingEntry) {
        // If the entry exists, update it
        $sqlUpdate = "UPDATE advertisers_ads_partners 
                      SET title_ads = :title_ads, 
                          description_ads = :description_ads, 
                          landingpage_ads = :landingpage_ads, 

                          image_url = :image_url, 

                          ispublished = :ispublished, 
                          total_click = :total_click, 
                          current_click = :current_click ,
                          budget_per_click_textads = :budget_per_click_textads ,
                          is_expired= :is_expired ,
                          expired_date= :expired_date ,
                          is_paused= :is_paused , 
                         paused_date = :paused_date  ,
                        budget_allocation= :budget_allocation  ,
                        current_spending= :current_spending 

                      WHERE local_ads_id = :local_ads_id";

        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindParam(':title_ads', $title_ads, PDO::PARAM_STR);
        $stmtUpdate->bindParam(':description_ads', $description_ads, PDO::PARAM_STR);
        
        $stmtUpdate->bindParam(':landingpage_ads', $landingpage_ads, PDO::PARAM_STR);

        $stmtUpdate->bindParam(':image_url', $image_url, PDO::PARAM_STR);


        $stmtUpdate->bindParam(':ispublished', $ispublished, PDO::PARAM_INT);
        $stmtUpdate->bindParam(':total_click', $total_click, PDO::PARAM_INT);
        $stmtUpdate->bindParam(':current_click', $current_click, PDO::PARAM_INT);
        
        $stmtUpdate->bindParam(':budget_per_click_textads', $budget_per_click_textads, PDO::PARAM_INT);

    $stmtUpdate->bindParam(':is_expired', $is_expired, PDO::PARAM_INT);

     $stmtUpdate->bindParam(':expired_date', $expired_date, PDO::PARAM_STR);

    $stmtUpdate->bindParam(':is_paused', $is_paused, PDO::PARAM_STR);
     
     
     $stmtUpdate->bindParam(':paused_date', $paused_date, PDO::PARAM_STR);

$stmtUpdate->bindParam(':budget_allocation',$budget_allocation, PDO::PARAM_INT);

$stmtUpdate->bindParam(':current_spending',$current_spending, PDO::PARAM_INT);


        $stmtUpdate->bindParam(':local_ads_id', $local_ads_id, PDO::PARAM_INT);
        $stmtUpdate->execute();

        // Success message
        $return .=  "Ad successfully updated! ID: " . $existingEntry['id']. " title_ads: ".$title_ads;
        $lastInsertId = $existingEntry['id'];
    } else {
        // If the entry does not exist, insert a new one
        $sqlInsert = "INSERT INTO advertisers_ads_partners (local_ads_id, providers_name, providers_domain_url, advertisers_id, title_ads, description_ads, 
            landingpage_ads, image_url ,  

            ispublished, regdate, total_click, current_click,budget_per_click_textads ,budget_allocation ,current_spending)
                      VALUES (:local_ads_id, :providers_name, :providers_domain_url, :advertisers_id, :title_ads, :description_ads, :landingpage_ads,
                          :image_url,
                          :ispublished , 
                          :regdate, :total_click, :current_click,:budget_per_click_textads , :budget_allocation,:current_spending

                      )";

        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->bindParam(':local_ads_id', $local_ads_id, PDO::PARAM_STR);
        $stmtInsert->bindParam(':providers_name', $providers_name, PDO::PARAM_STR);
        $stmtInsert->bindParam(':providers_domain_url', $providers_domain_url, PDO::PARAM_STR);
        $stmtInsert->bindParam(':advertisers_id', $advertisers_id, PDO::PARAM_INT);
        
        $stmtInsert->bindParam(':title_ads', $title_ads, PDO::PARAM_STR);
        $stmtInsert->bindParam(':description_ads', $description_ads, PDO::PARAM_STR);
        
        $stmtInsert->bindParam(':landingpage_ads', $landingpage_ads, PDO::PARAM_STR);

        $stmtInsert->bindParam(':image_url', $image_url, PDO::PARAM_STR);


        $stmtInsert->bindParam(':ispublished', $ispublished, PDO::PARAM_INT);
        

        $stmtInsert->bindParam(':regdate', $regdate, PDO::PARAM_STR);
        $stmtInsert->bindParam(':total_click', $total_click, PDO::PARAM_INT);
        $stmtInsert->bindParam(':current_click', $current_click, PDO::PARAM_INT);

        $stmtInsert->bindParam(':budget_per_click_textads', $budget_per_click_textads, PDO::PARAM_INT);



        $stmtInsert->bindParam(':budget_allocation', $budget_allocation, PDO::PARAM_INT);

        $stmtInsert->bindParam(':current_spending', $current_spending, PDO::PARAM_INT);



        $stmtInsert->execute();

        // Get the last inserted ID
        $lastInsertId = $pdo->lastInsertId();



        // Success message
         $return .= "<br>Ad successfully inserted! Last inserted ID: " . $lastInsertId. ", title_ads" . $title_ads;
    }

    // Close the database connection
    $pdo = null;

    // Return the last inserted or updated ID
    return $return;
}

/*

Berikut adalah penjelasan detail mengenai fungsi dari kode PHP yang Anda berikan:

### **1. Inisialisasi dan Koneksi ke Database:**
- **Inklusi File:** Kode ini dimulai dengan menyertakan file `db.php` dan `function.php`, yang kemungkinan berisi konfigurasi database dan fungsi tambahan yang digunakan dalam skrip ini.
- **Pengaturan Error Log:** Lokasi file log untuk menyimpan error ditetapkan dengan menggunakan `ini_set("error_log", "errr_.txt");`.
- **Membaca Input JSON:** Kode mengambil input JSON dari HTTP request body menggunakan `file_get_contents('php://input')`, kemudian mendekodenya menjadi array PHP dengan `json_decode`.
- **Koneksi ke Database:**
  - **PDO:** Koneksi ke database dilakukan menggunakan PDO, yang merupakan cara yang aman untuk berinteraksi dengan database karena mendukung prepared statements yang melindungi dari SQL injection.
  - **MySQLi:** Koneksi juga dibuat menggunakan MySQLi, meskipun tidak digunakan secara ekstensif dalam kode ini.

### **2. Mengecek Header untuk Kunci Autentikasi:**
- **Pengambilan Header:** Kode ini mengambil header `public_key` dan `secret_key` dari request HTTP.
- **Validasi Header:** Kode memeriksa apakah `public_key` dan `secret_key` ada. Jika salah satu tidak ada, akan dikembalikan respons JSON yang menunjukkan bahwa header yang dibutuhkan tidak ada.

### **3. Memvalidasi Kredensial Penyedia (Provider Credentials):**
- **Fungsi `checkProviderCredentials`:** Fungsi ini (yang kemungkinan didefinisikan di `function.php`) digunakan untuk memvalidasi apakah `public_key` dan `secret_key` yang diberikan sesuai dengan `providers_domain_url`. Jika kredensial tidak valid, kode ini akan mengirimkan respons JSON yang menunjukkan kesalahan dan mengakhiri eksekusi.

### **4. Memproses Data Iklan:**
- **Pemeriksaan `title_ads`:** Kode memeriksa apakah data `title_ads` ada di dalam JSON yang dikirimkan. Jika ya, maka proses berikutnya dilakukan.
- **Ekstraksi Data Iklan:** Data yang diperlukan dari input JSON, seperti `providers_name`, `providers_domain_url`, `advertisers_id`, `local_ads_id`, `ispublished`, `title_ads`, `description_ads`, `landingpage_ads`, `total_click`, `current_click`, dan `budget_per_click_textads` diekstrak untuk digunakan dalam proses berikutnya.

### **5. Memasukkan atau Memperbarui Data Iklan di Database:**
- **Fungsi `insertOrUpdateAdvertisersAdsPartner`:** Fungsi ini menerima parameter yang telah diekstrak dari input JSON dan memutuskan apakah akan memasukkan data baru atau memperbarui data yang ada di tabel `advertisers_ads_partners`.
  - **Memeriksa Keberadaan Data:** Kode memeriksa apakah iklan dengan `local_ads_id` dan `providers_domain_url` yang sama sudah ada di dalam tabel `advertisers_ads_partners`.
  - **Memperbarui Data Jika Ada:** Jika data sudah ada, kode akan memperbarui kolom seperti `title_ads`, `description_ads`, `landingpage_ads`, `ispublished`, `total_click`, `current_click`, dan `budget_per_click_textads`.
  - **Memasukkan Data Baru Jika Tidak Ada:** Jika data tidak ada, kode akan memasukkan data baru ke dalam tabel dengan informasi yang diberikan.
  - **Pengembalian Status:** Fungsi ini mengembalikan pesan yang menunjukkan apakah data berhasil diperbarui atau dimasukkan, termasuk ID terakhir yang dimasukkan atau diperbarui.

### **6. Pengiriman Respons JSON:**
- Setelah data diproses oleh fungsi `insertOrUpdateAdvertisersAdsPartner`, kode ini mengirimkan respons JSON yang menunjukkan status berhasil atau gagal dari operasi tersebut.

### **Ringkasan:**
Kode ini adalah endpoint API yang digunakan untuk menyinkronkan data iklan dari penyedia jaringan iklan (ad network partner) dengan sistem lokal. Kode ini menerima input JSON, memvalidasi kredensial penyedia, dan kemudian memasukkan atau memperbarui data iklan di dalam database. Proses ini melibatkan pengecekan apakah data iklan sudah ada atau belum di dalam tabel `advertisers_ads_partners`. Jika ada, data tersebut diperbarui; jika tidak, data baru akan dimasukkan. Kode ini memastikan bahwa data iklan dari mitra jaringan iklan selalu sinkron dengan sistem lokal.


*/

?>