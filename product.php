<?php require 'includes/db.php';
$id = (int)($_GET['id'] ?? 0);
$st = $db->prepare("SELECT p.*, k.nama_kategori, j.nama_toko FROM produk p JOIN kategori k USING(id_kategori) JOIN penjual j USING(id_penjual) WHERE id_produk=?");
$st->bind_param('i', $id); $st->execute(); $p = $st->get_result()->fetch_assoc();
if (!$p) { http_response_code(404); die('Produk tidak ditemukan.'); }
$title = $p['nama_produk']; include 'includes/header.php'; ?>
<div class="row"><div class="col-md-5"><img src="<?= e(img($p['gambar_url'],$p['nama_produk'])) ?>" class="detail-img" alt=""></div>
<div class="col-md-7"><span class="badge bg-secondary"><?= e($p['nama_kategori']) ?></span>
<h2 class="mt-2"><?= e($p['nama_produk']) ?></h2>
<p class="text-muted">Penjual: <?= e($p['nama_toko']) ?></p>
<h3 class="price fs-2"><?= rp($p['harga']) ?></h3>
<p>Stok: <?= (int)$p['stok'] ?></p>
<p><?= nl2br(e($p['deskripsi'])) ?></p>
<?php if ($p['stok'] > 0): ?>
<form method="post" action="cart.php" class="d-flex gap-2" style="max-width:300px">
<input type="hidden" name="action" value="add"><input type="hidden" name="id" value="<?= $p['id_produk'] ?>">
<input type="number" name="qty" value="1" min="1" max="<?= (int)$p['stok'] ?>" class="form-control">
<button class="btn btn-grad">Tambah ke Keranjang</button></form>
<?php else: ?><div class="alert alert-danger">Stok habis</div><?php endif; ?>
<a href="index.php" class="btn btn-link mt-3">&larr; Kembali</a></div></div>
<?php include 'includes/footer.php'; ?>
