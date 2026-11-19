<?php
// Konfigurasi koneksi database untuk XAMPP.
// Sesuaikan DB_NAME jika nama database lokal berbeda.
$host = "localhost";
$user = "root";
$pass = "";
$db   = "marketplace";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    http_response_code(500);
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode([
        "status" => false,
        "message" => "Koneksi database gagal"
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$conn->set_charset("utf8mb4");
