<?php
require 'includes/db.php';

$telp = trim($_GET['telepon'] ?? '');
$orders = [];

if ($telp !== '') {
  $st = $db->prepare("SELECT o.id_pesanan, o.tanggal, o.total, o.status,
      GROUP_CONCAT(CONCAT(p.nama_produk,' x',d.jumlah) SEPARATOR ', ') AS item
    FROM pesanan o
    JOIN pembeli b ON b.id_pembeli = o.id_pembeli
    LEFT JOIN detail_pesanan d ON d.id_pesanan = o.id_pesanan
    LEFT JOIN produk p ON p.id_produk = d.id_produk
    WHERE b.telepon = ?
    GROUP BY o.id_pesanan, o.tanggal, o.total, o.status
    ORDER BY o.id_pesanan DESC");
  $st->bind_param('s', $telp);
  $st->execute();
  $orders = $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

$stList = ['baru'=>'primary','diproses'=>'warning','dikirim'=>'info','selesai'=>'success'];
$title = 'Status Pesanan'; include 'includes/header.php';
?>
<h3 class="mb-3">Status Pesanan</h3>

<form method="get" class="bg-white p-3 rounded shadow-sm mb-4 d-flex gap-2">
  <input name="telepon" class="form-control" placeholder="Masukkan nomor telepon saat memesan" value="<?= e($telp) ?>" required>
  <button class="btn btn-grad px-4">Cari</button>
</form>

<?php if ($telp !== ''): ?>
  <?php if (!$orders): ?>
    <div class="alert alert-warning">Tidak ada pesanan untuk nomor tersebut.</div>
  <?php else: ?>
    <div class="bg-white p-3 rounded shadow-sm mb-4"><div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr><th>#</th><th>Tanggal</th><th>Item</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): $c = $stList[$o['status']] ?? 'secondary'; ?>
          <tr>
            <td><?= (int)$o['id_pesanan'] ?></td>
            <td class="text-nowrap"><?= e($o['tanggal']) ?></td>
            <td class="small"><?= e($o['item']) ?></td>
            <td class="text-nowrap"><?= rp($o['total']) ?></td>
            <td><span class="badge bg-<?= $c ?> <?= in_array($c, ['warning','info']) ? 'text-dark' : '' ?>"><?= e(ucfirst($o['status'])) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div></div>
  <?php endif; ?>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>