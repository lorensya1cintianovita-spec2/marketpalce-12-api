<?php require 'includes/db.php';
$cart = $_SESSION['cart'] ?? []; $err = ''; $done = 0;
if (!$cart && empty($_GET['ok'])) { header('Location: cart.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cart) {
  $nama = trim($_POST['nama'] ?? ''); $tel = trim($_POST['telepon'] ?? ''); $alamat = trim($_POST['alamat'] ?? '');
  if (!$nama || !$tel || !$alamat) $err = 'Semua data wajib diisi.';
  else {
    $db->begin_transaction();
    try {
      $lines = []; $total = 0;
      foreach ($cart as $pid => $qty) {
        $st = $db->prepare("SELECT harga, stok, nama_produk FROM produk WHERE id_produk=? FOR UPDATE");
        $st->bind_param('i', $pid); $st->execute(); $p = $st->get_result()->fetch_assoc();
        if (!$p || $p['stok'] < $qty) throw new Exception('Stok tidak cukup untuk: ' . ($p['nama_produk'] ?? "ID $pid"));
        $lines[] = [$pid, $qty, $p['harga']]; $total += $qty * $p['harga'];
      }
      $st = $db->prepare("INSERT INTO pembeli (nama,telepon,alamat) VALUES (?,?,?)");
      $st->bind_param('sss', $nama, $tel, $alamat); $st->execute(); $idb = $db->insert_id;
      $st = $db->prepare("INSERT INTO pesanan (id_pembeli,total) VALUES (?,?)");
      $st->bind_param('id', $idb, $total); $st->execute(); $idp = $db->insert_id;
      foreach ($lines as [$pid, $qty, $h]) {
        $sub = $qty * $h;
        $st = $db->prepare("INSERT INTO detail_pesanan (id_pesanan,id_produk,jumlah,harga_satuan,subtotal) VALUES (?,?,?,?,?)");
        $st->bind_param('iiidd', $idp, $pid, $qty, $h, $sub); $st->execute();
        $st = $db->prepare("UPDATE produk SET stok = stok - ? WHERE id_produk=?");
        $st->bind_param('ii', $qty, $pid); $st->execute();
      }
      $db->commit(); $_SESSION['cart'] = []; $done = $idp;
    } catch (Exception $ex) { $db->rollback(); $err = $ex->getMessage(); }
  }
}
$title = 'Checkout'; include 'includes/header.php'; ?>
<?php if ($done): ?>
<div class="alert alert-success"><h4>Pesanan berhasil dibuat!</h4>Nomor pesanan Anda: <strong>#<?= $done ?></strong></div><a href="index.php" class="btn btn-primary">Kembali Belanja</a>
<?php else: ?>
<h3>Checkout</h3><?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
<form method="post" class="bg-white p-4 rounded shadow-sm" style="max-width:600px">
<div class="mb-3"><label class="form-label">Nama</label><input name="nama" class="form-control" value="<?= e($_POST['nama'] ?? '') ?>" required></div>
<div class="mb-3"><label class="form-label">Telepon</label><input name="telepon" class="form-control" value="<?= e($_POST['telepon'] ?? '') ?>" required></div>
<div class="mb-3"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="3" required><?= e($_POST['alamat'] ?? '') ?></textarea></div>
<button class="btn btn-grad">Buat Pesanan</button> <a href="cart.php" class="btn btn-link">Kembali</a></form>
<?php endif; include 'includes/footer.php'; ?>
