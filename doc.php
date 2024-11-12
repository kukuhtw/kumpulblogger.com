<?php

/*
doc.php
*/
include("db.php");

// Koneksi ke database menggunakan MySQLi
$mysqli = new mysqli($servername_db, $username_db, $password_db, $dbname_db);
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit("Database connection failed.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Technical List</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        /* Mengatur lebar kolom Function Name */
        .function-name {
            white-space: pre-wrap; /* Menampilkan teks dengan format baris baru */
            word-wrap: break-word; /* Memastikan kata-kata tidak melebihi lebar kolom */
        }
        
        /* Menambahkan ukuran modal yang lebih besar */
        .modal-lg {
            max-width: 80%;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2>Document Technical List</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Filename</th>
                <th>Function Name</th>
                <th>Description</th>
                <th>Last Update</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // Query untuk mengambil data dari tabel `document_technical`
        $sql = "SELECT * FROM document_technical order by id desc";
        $result = $mysqli->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . htmlspecialchars($row['filename']) . "</td>";
                echo "<td class='function-name'>" . nl2br(htmlspecialchars($row['function_name'])) . "</td>"; // Format multiline
                echo "<td><button type='button' class='btn btn-info' data-toggle='modal' data-target='#descriptionModal' data-description='" . htmlspecialchars($row['description'], ENT_QUOTES) . "'>Info Lebih Lanjut</button></td>";
                echo "<td>" . $row['last_update'] . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No records found</td></tr>";
        }

        $mysqli->close();
        ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="descriptionModal" tabindex="-1" role="dialog" aria-labelledby="descriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document"> <!-- Modal diperbesar -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="descriptionModalLabel">Description</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="descriptionModalBody">
                <!-- Konten Description akan dimuat di sini -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    $('#descriptionModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Tombol yang memicu modal
        var description = button.data('description'); // Ambil info dari atribut data-*
        var modal = $(this);
        modal.find('.modal-body').html(description); // Gunakan .html() untuk menampilkan tag HTML dengan benar
    });
</script>
</body>
</html>
