<?php require 'includes/db.php'; $title='Daftar Produk';
$q = '%' . ($_GET['q'] ?? '') . '%'; $kat = (int)($_GET['k'] ?? 0);
$st = $db->prepare("SELECT p.*, k.nama_kategori, j.nama_toko FROM produk p JOIN kategori k USING(id_kategori) JOIN penjual j USING(id_penjual) WHERE p.nama_produk LIKE ? AND (? = 0 OR p.id_kategori = ?) ORDER BY p.id_produk DESC");
$st->bind_param('sii', $q, $kat, $kat); $st->execute(); $rs = $st->get_result();
$kts = $db->query("SELECT * FROM kategori");
include 'includes/header.php'; ?>
<div class="hero">
<h1>Temukan produk favoritmu <i class="bi bi-stars"></i></h1>
<p class="mb-4 opacity-75">Belanja mudah dari berbagai toko, langsung pesan dalam hitungan detik.</p>
<form class="d-flex gap-2"><?php if ($kat): ?><input type="hidden" name="k" value="<?= $kat ?>"><?php endif; ?>
<input name="q" class="form-control" placeholder="Cari produk..." value="<?= e($_GET['q'] ?? '') ?>"><button class="btn"><i class="bi bi-search"></i> Cari</button></form></div>
<div class="mb-3"><a href="index.php" class="pill <?= !$kat ? 'active' : '' ?>">Semua</a>
<?php while ($k = $kts->fetch_assoc()): ?><a href="?k=<?= $k['id_kategori'] ?>" class="pill <?= $kat == $k['id_kategori'] ? 'active' : '' ?>"><?= e($k['nama_kategori']) ?></a><?php endwhile; ?></div>
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
<?php if ($rs->num_rows === 0): ?><div class="col-12"><div class="alert alert-light text-center">Produk tidak ditemukan.</div></div><?php endif; ?>
<?php while ($p = $rs->fetch_assoc()): ?>
<div class="col"><div class="card card-p h-100">
<div class="position-relative"><img src="<?= e(img($p['gambar_url'], $p['nama_produk'])) ?>" class="thumb" alt="<?= e($p['nama_produk']) ?>"><span class="cat"><?= e($p['nama_kategori']) ?></span></div>
<div class="card-body">
<h5 class="fw-semibold mb-1"><?= e($p['nama_produk']) ?></h5>
<p class="text-muted small mb-2"><i class="bi bi-shop"></i> <?= e($p['nama_toko']) ?> &middot; Stok <?= (int)$p['stok'] ?></p>
<div class="price"><?= rp($p['harga']) ?></div></div>
<div class="card-body pt-0 d-flex gap-2">
<a href="product.php?id=<?= $p['id_produk'] ?>" class="btn btn-soft btn-sm">Detail</a>
<?php if ($p['stok'] > 0): ?><form method="post" action="cart.php"><input type="hidden" name="action" value="add"><input type="hidden" name="id" value="<?= $p['id_produk'] ?>"><button class="btn btn-grad btn-sm"><i class="bi bi-cart-plus"></i> Keranjang</button></form>
<?php else: ?><span class="text-danger small align-self-center">Stok habis</span><?php endif; ?>
</div></div></div>
<?php endwhile; ?></div>
<?php include 'includes/footer.php'; ?>
