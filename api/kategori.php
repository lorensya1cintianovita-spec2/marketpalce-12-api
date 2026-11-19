<?php
require_once __DIR__ . "/koneksi.php";
require_once __DIR__ . "/response.php";

requireMethod("GET");

$result = $conn->query("SELECT id_kategori, nama_kategori FROM kategori ORDER BY id_kategori ASC");
if (!$result) {
    jsonResponse(500, false, "Gagal mengambil data kategori.");
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
jsonResponse(200, true, "Daftar kategori berhasil ditemukan.", $data);
