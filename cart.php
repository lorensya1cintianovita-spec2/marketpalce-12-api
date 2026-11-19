<?php require 'includes/db.php';
$cart = &$_SESSION['cart']; if (!is_array($cart)) $cart = [];
$a = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
if ($a === 'add' && $id) { $cart[$id] = ($cart[$id] ?? 0) + max(1, (int)($_POST['qty'] ?? 1)); header('Location: cart.php'); exit; }
if ($a === 'update' && $id) { $q = (int)$_POST['qty']; if ($q > 0) $cart[$id] = $q; else unset($cart[$id]); header('Location: cart.php'); exit; }
if ($a === 'remove') { unset($cart[$id]); header('Location: cart.php'); exit; }
$items = []; $total = 0;
if ($cart) {
  $ids = implode(',', array_map('intval', array_keys($cart)));
  $rs = $db->query("SELECT * FROM produk WHERE id_produk IN ($ids)");
  while ($p = $rs->fetch_assoc()) { $p['qty'] = min($cart[$p['id_produk']], max(1,$p['stok'])); $p['sub'] = $p['qty'] * $p['harga']; $total += $p['sub']; $items[] = $p; }
}
$title = 'Keranjang'; include 'includes/header.php'; ?>
<h3>Keranjang Belanja</h3>
<?php if (!$items): ?><div class="alert alert-info">Keranjang kosong. <a href="index.php">Belanja dulu</a></div>
<?php else: ?>
<div class="table-responsive"><table class="table bg-white align-middle">
<thead><tr><th>Produk</th><th>Harga</th><th style="width:150px">Jumlah</th><th>Subtotal</th><th></th></tr></thead><tbody>
<?php foreach ($items as $p): ?><tr><td><?= e($p['nama_produk']) ?></td><td><?= rp($p['harga']) ?></td>
<td><form method="post" class="d-flex gap-1"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= $p['id_produk'] ?>">
<input type="number" name="qty" value="<?= $p['qty'] ?>" min="0" max="<?= (int)$p['stok'] ?>" class="form-control form-control-sm"><button class="btn btn-sm btn-outline-primary">OK</button></form></td>
<td><?= rp($p['sub']) ?></td>
<td><form method="post"><input type="hidden" name="action" value="remove"><input type="hidden" name="id" value="<?= $p['id_produk'] ?>"><button class="btn btn-sm btn-danger">Hapus</button></form></td></tr>
<?php endforeach; ?></tbody>
<tfoot><tr><th colspan="3" class="text-end">Total</th><th colspan="2"><?= rp($total) ?></th></tr></tfoot></table></div>
<a href="index.php" class="btn btn-outline-secondary">Lanjut Belanja</a> <a href="checkout.php" class="btn btn-grad">Checkout</a>
<?php endif; include 'includes/footer.php'; ?>
