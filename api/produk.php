<?php
require_once __DIR__ . "/koneksi.php";
require_once __DIR__ . "/response.php";

requireMethod("GET");

if (isset($_GET["id"])) {
    $id = filter_var($_GET["id"], FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        jsonResponse(400, false, "ID produk harus berupa angka positif.");
    }

    $sql = "SELECT p.id_produk, p.nama_produk, p.deskripsi, p.harga, p.stok,
                   p.gambar_url, p.created_at, k.id_kategori, k.nama_kategori,
                   j.id_penjual, j.nama_toko
            FROM produk p
            JOIN kategori k ON p.id_kategori = k.id_kategori
            JOIN penjual j ON p.id_penjual = j.id_penjual
            WHERE p.id_produk = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $produk = $result->fetch_assoc();

    if (!$produk) {
        jsonResponse(404, false, "Produk tidak ditemukan.");
    }
    jsonResponse(200, true, "Detail produk berhasil ditemukan.", $produk);
}

$sql = "SELECT p.id_produk, p.nama_produk, p.deskripsi, p.harga, p.stok,
               p.gambar_url, k.nama_kategori, j.nama_toko
        FROM produk p
        JOIN kategori k ON p.id_kategori = k.id_kategori
        JOIN penjual j ON p.id_penjual = j.id_penjual
        ORDER BY p.id_produk DESC";
$result = $conn->query($sql);

if (!$result) {
    jsonResponse(500, false, "Gagal mengambil data produk.");
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
jsonResponse(200, true, "Daftar produk berhasil ditemukan.", $data);
