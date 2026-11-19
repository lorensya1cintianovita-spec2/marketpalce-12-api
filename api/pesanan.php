<?php
require_once __DIR__ . "/koneksi.php";
require_once __DIR__ . "/response.php";

requireMethod("POST");

$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

if (!is_array($input)) {
    jsonResponse(400, false, "Body request harus berupa JSON yang valid.");
}

$nama = trim($input["nama"] ?? "");
$telepon = trim($input["telepon"] ?? "");
$alamat = trim($input["alamat"] ?? "");
$items = $input["items"] ?? null;

if ($nama === "" || $telepon === "" || $alamat === "") {
    jsonResponse(422, false, "Nama, telepon, dan alamat wajib diisi.");
}
if (!is_array($items) || count($items) === 0) {
    jsonResponse(422, false, "Items harus berupa array dan minimal berisi satu produk.");
}

// Validasi bentuk item terlebih dahulu.
$cleanItems = [];
foreach ($items as $item) {
    $idProduk = filter_var($item["id_produk"] ?? null, FILTER_VALIDATE_INT);
    $jumlah = filter_var($item["jumlah"] ?? null, FILTER_VALIDATE_INT);
    if (!$idProduk || $idProduk < 1 || !$jumlah || $jumlah < 1) {
        jsonResponse(422, false, "Setiap item harus memiliki id_produk dan jumlah berupa bilangan positif.");
    }
    $cleanItems[] = ["id_produk" => $idProduk, "jumlah" => $jumlah];
}

$conn->begin_transaction();

try {
    // Simpan data pembeli.
    $stmtPembeli = $conn->prepare("INSERT INTO pembeli (nama, telepon, alamat) VALUES (?, ?, ?)");
    $stmtPembeli->bind_param("sss", $nama, $telepon, $alamat);
    if (!$stmtPembeli->execute()) {
        throw new Exception("Gagal menyimpan data pembeli.");
    }
    $idPembeli = $conn->insert_id;

    $total = 0;
    $detail = [];

    // Kunci baris produk saat memeriksa stok agar stok tidak terjual melebihi jumlah tersedia.
    $stmtProduk = $conn->prepare("SELECT nama_produk, harga, stok FROM produk WHERE id_produk = ? FOR UPDATE");
    foreach ($cleanItems as $item) {
        $idProduk = $item["id_produk"];
        $jumlah = $item["jumlah"];

        $stmtProduk->bind_param("i", $idProduk);
        $stmtProduk->execute();
        $produk = $stmtProduk->get_result()->fetch_assoc();

        if (!$produk) {
            throw new Exception("Produk dengan ID " . $idProduk . " tidak ditemukan.");
        }
        if ((int)$produk["stok"] < $jumlah) {
            throw new Exception("Stok produk " . $produk["nama_produk"] . " tidak mencukupi.");
        }

        $harga = (float)$produk["harga"];
        $subtotal = $harga * $jumlah;
        $total += $subtotal;

        $detail[] = [
            "id_produk" => $idProduk,
            "nama_produk" => $produk["nama_produk"],
            "jumlah" => $jumlah,
            "harga_satuan" => $harga,
            "subtotal" => $subtotal
        ];
    }

    // Buat header pesanan.
    $stmtPesanan = $conn->prepare("INSERT INTO pesanan (id_pembeli, total, status) VALUES (?, ?, 'baru')");
    $stmtPesanan->bind_param("id", $idPembeli, $total);
    if (!$stmtPesanan->execute()) {
        throw new Exception("Gagal menyimpan pesanan.");
    }
    $idPesanan = $conn->insert_id;

    // Simpan detail pesanan dan kurangi stok.
    $stmtDetail = $conn->prepare(
        "INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga_satuan, subtotal)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmtStok = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk = ?");

    foreach ($detail as $d) {
        $idProduk = $d["id_produk"];
        $jumlah = $d["jumlah"];
        $harga = $d["harga_satuan"];
        $subtotal = $d["subtotal"];

        $stmtDetail->bind_param("iiidd", $idPesanan, $idProduk, $jumlah, $harga, $subtotal);
        if (!$stmtDetail->execute()) {
            throw new Exception("Gagal menyimpan detail pesanan.");
        }

        $stmtStok->bind_param("ii", $jumlah, $idProduk);
        if (!$stmtStok->execute()) {
            throw new Exception("Gagal memperbarui stok produk.");
        }
    }

    $conn->commit();

    jsonResponse(201, true, "Pesanan berhasil dibuat.", [
        "id_pesanan" => $idPesanan,
        "id_pembeli" => $idPembeli,
        "total" => number_format($total, 2, ".", ""),
        "status" => "baru",
        "detail" => $detail
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    jsonResponse(400, false, $e->getMessage());
}
