<?php
require 'includes/db.php';

$msg = ''; $msgType = 'info';

/* ================= HELPER ================= */
function is_local_img($g) {
  return $g !== '' && !preg_match('#^https?://#i', $g) && basename($g) === $g;
}
function del_local_img($g) {
  if (is_local_img($g) && is_file(__DIR__ . '/uploads/' . $g)) @unlink(__DIR__ . '/uploads/' . $g);
}
function flash($text, $type = 'success') { $_SESSION['flash'] = [$text, $type]; header('Location: admin.php'); exit; }

/** Proses upload gambar. Return nama file baru, '' jika tidak ada file, atau isi $err jika gagal. */
function upload_image($f, &$err) {
  if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) return '';
  if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > 2*1024*1024) { $err = 'Ukuran gambar melebihi 2MB.'; return ''; }
  if ($f['error'] !== UPLOAD_ERR_OK) { $err = 'Upload gagal (kode ' . $f['error'] . ').'; return ''; }
  $info = @getimagesize($f['tmp_name']);
  $ext  = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'][$info['mime'] ?? ''] ?? null;
  if (!$ext) { $err = 'Format harus JPG, PNG, WEBP, atau GIF.'; return ''; }
  $dir = __DIR__ . '/uploads';
  if (!is_dir($dir) && !mkdir($dir, 0755, true)) { $err = 'Folder uploads tidak bisa dibuat. Buat manual lewat File Manager.'; return ''; }
  $fn = uniqid('p_') . '.' . $ext;            // ekstensi ditentukan server dari isi file
  if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $fn)) { $err = 'Gagal menyimpan file ke folder uploads (cek izin folder).'; return ''; }
  return $fn;
}
/** Cari penjual berdasarkan nama toko; jika belum ada, otomatis dibuat. Return id_penjual. */
function get_penjual_id($db, $nama) {
  $nama = trim($nama);
  if ($nama === '') throw new Exception('Nama penjual/toko wajib diisi.');
  $q = $db->prepare("SELECT id_penjual FROM penjual WHERE nama_toko=? LIMIT 1");
  $q->bind_param('s', $nama); $q->execute();
  $row = $q->get_result()->fetch_assoc();
  if ($row) return (int)$row['id_penjual'];
  $ins = $db->prepare("INSERT INTO penjual (nama_toko) VALUES (?)");
  $ins->bind_param('s', $nama); $ins->execute();
  return (int)$db->insert_id;
}
/** Cari kategori berdasarkan nama; jika belum ada, otomatis dibuat. Return id_kategori. */
function get_kategori_id($db, $nama) {
  $nama = trim($nama);
  if ($nama === '') throw new Exception('Nama kategori wajib diisi.');
  $q = $db->prepare("SELECT id_kategori FROM kategori WHERE nama_kategori=? LIMIT 1");
  $q->bind_param('s', $nama); $q->execute();
  $row = $q->get_result()->fetch_assoc();
  if ($row) return (int)$row['id_kategori'];
  $ins = $db->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
  $ins->bind_param('s', $nama); $ins->execute();
  return (int)$db->insert_id;
}
function old_image($db, $id) {
  $q = $db->prepare("SELECT gambar_url FROM produk WHERE id_produk=?");
  $q->bind_param('i', $id); $q->execute();
  return $q->get_result()->fetch_assoc()['gambar_url'] ?? '';
}

/* ================= LOGIN / LOGOUT (sebelum output) ================= */
if (isset($_GET['logout'])) { unset($_SESSION['admin']); header('Location: admin.php'); exit; }
if (isset($_POST['login'])) {
  if (hash_equals(ADMIN_PASSWORD, $_POST['password'] ?? '')) { $_SESSION['admin'] = 1; header('Location: admin.php'); exit; }
  $msg = 'Password salah.'; $msgType = 'danger';
}
$isAdmin = !empty($_SESSION['admin']);

if (!$isAdmin) {
  $title = 'Admin'; include 'includes/header.php'; ?>
  <div class="d-flex justify-content-center py-5">
    <form method="post" class="bg-white p-4 rounded shadow-sm w-100" style="max-width:360px">
      <h4 class="mb-3">Login Admin</h4>
      <?php if ($msg): ?><div class="alert alert-<?= $msgType ?>"><?= e($msg) ?></div><?php endif; ?>
      <input type="password" name="password" class="form-control mb-3" placeholder="Password" required autofocus>
      <button name="login" class="btn btn-primary w-100">Masuk</button>
    </form>
  </div>
  <?php include 'includes/footer.php'; exit;
}

if (!empty($_SESSION['flash'])) { [$msg, $msgType] = $_SESSION['flash']; unset($_SESSION['flash']); }

$add = ['nama_produk'=>'','nama_toko'=>'','nama_kategori'=>'','harga'=>'','stok'=>'','gambar_url'=>'','deskripsi'=>''];

/* ================= TAMBAH PRODUK ================= */
if (isset($_POST['add'])) {
  $add = [
    'nama_produk' => trim($_POST['nama_produk'] ?? ''), 'nama_toko' => trim($_POST['nama_toko'] ?? ''),
    'nama_kategori' => trim($_POST['nama_kategori'] ?? ''), 'harga' => (float)($_POST['harga'] ?? 0),
    'stok' => (int)($_POST['stok'] ?? 0), 'gambar_url' => trim($_POST['gambar_url'] ?? ''), 'deskripsi' => trim($_POST['deskripsi'] ?? ''),
  ];
  $err = ''; $gb = '';
  if ($add['nama_produk'] === '') $err = 'Nama produk wajib diisi.';
  elseif ($add['nama_toko'] === '') $err = 'Nama penjual/toko wajib diisi.';
  elseif ($add['nama_kategori'] === '') $err = 'Kategori wajib diisi.';
  elseif ($add['harga'] < 0 || $add['stok'] < 0) $err = 'Harga dan stok tidak boleh negatif.';
  elseif ($add['gambar_url'] !== '' && !preg_match('#^https?://#i', $add['gambar_url'])) $err = 'URL gambar harus diawali http:// atau https://';
  if (!$err) {
    $up = upload_image($_FILES['gambar'] ?? null, $err);
    $gb = $up !== '' ? $up : $add['gambar_url'];
  }
  if (!$err) {
    try {
      $pjId = get_penjual_id($db, $add['nama_toko']);
      $ktId = get_kategori_id($db, $add['nama_kategori']);
      $st = $db->prepare("INSERT INTO produk (id_penjual,id_kategori,nama_produk,deskripsi,harga,stok,gambar_url) VALUES (?,?,?,?,?,?,?)");
      $st->bind_param('iissdis', $pjId, $ktId, $add['nama_produk'], $add['deskripsi'], $add['harga'], $add['stok'], $gb);
      $st->execute();
      flash('Produk berhasil ditambahkan.');
    } catch (Throwable $ex) { del_local_img($gb); $err = 'Gagal menyimpan: ' . $ex->getMessage(); }
  }
  $msg = $err; $msgType = 'danger';    // isian form tetap dipertahankan
}

/* ================= EDIT DATA PRODUK (dari daftar) ================= */
if (isset($_POST['update'])) {
  $id = (int)$_POST['id']; $old = old_image($db, $id);
  $nm = trim($_POST['nama_produk'] ?? ''); $ds = trim($_POST['deskripsi'] ?? '');
  $hg = (float)$_POST['harga']; $sk = (int)$_POST['stok']; $url = trim($_POST['gambar_url'] ?? '');
  $gb = $old;
  if ($nm === '') flash('Nama produk wajib diisi.', 'danger');
  if ($hg < 0 || $sk < 0) flash('Harga dan stok tidak boleh negatif.', 'danger');
  if (!empty($_POST['hapus_gambar'])) $gb = '';
  if ($url !== '' && $url !== $old) {
    if (!preg_match('#^https?://#i', $url)) flash('URL gambar harus diawali http:// atau https://', 'danger');
    $gb = $url;
  }
  try {
    $pj = get_penjual_id($db, $_POST['nama_toko'] ?? '');
    $kt = get_kategori_id($db, $_POST['nama_kategori'] ?? '');
    $st = $db->prepare("UPDATE produk SET id_penjual=?,id_kategori=?,nama_produk=?,deskripsi=?,harga=?,stok=?,gambar_url=? WHERE id_produk=?");
    $st->bind_param('iissdisi', $pj, $kt, $nm, $ds, $hg, $sk, $gb, $id);
    $st->execute();
    if ($gb !== $old) del_local_img($old);
    flash('Produk berhasil diperbarui.');
  } catch (Throwable $ex) { flash('Gagal menyimpan: ' . $ex->getMessage(), 'danger'); }
}

/* ================= GANTI FOTO (tombol di daftar produk) ================= */
if (isset($_POST['set_foto'])) {
  $id = (int)$_POST['id']; $err = '';
  $old = old_image($db, $id);
  $fn = upload_image($_FILES['gambar'] ?? null, $err);
  if ($err) flash($err, 'danger');
  if ($fn === '') flash('Pilih file foto terlebih dahulu.', 'warning');
  try {
    $st = $db->prepare("UPDATE produk SET gambar_url=? WHERE id_produk=?");
    $st->bind_param('si', $fn, $id); $st->execute();
    del_local_img($old);
    flash('Foto produk berhasil diganti.');
  } catch (Throwable $ex) { del_local_img($fn); flash('Gagal menyimpan foto: ' . $ex->getMessage(), 'danger'); }
}

/* ================= HAPUS PRODUK ================= */
if (isset($_POST['delete'])) {
  $id = (int)$_POST['id'];
  $q = $db->prepare("SELECT COUNT(*) c FROM detail_pesanan WHERE id_produk=?");
  $q->bind_param('i', $id); $q->execute();
  if ($q->get_result()->fetch_assoc()['c'] > 0) flash('Produk sudah ada di pesanan, tidak dapat dihapus (set stok 0).', 'warning');
  $g = old_image($db, $id);
  $st = $db->prepare("DELETE FROM produk WHERE id_produk=?"); $st->bind_param('i', $id); $st->execute();
  del_local_img($g);
  flash('Produk dihapus.');
}

/* ================= STATUS PESANAN ================= */
if (isset($_POST['set_status'])) {
  $okSt = ['baru','diproses','dikirim','selesai']; $sNew = $_POST['status'] ?? ''; $idp = (int)($_POST['id_pesanan'] ?? 0);
  if ($idp && in_array($sNew, $okSt, true)) {
    $st = $db->prepare("UPDATE pesanan SET status=? WHERE id_pesanan=?"); $st->bind_param('si', $sNew, $idp); $st->execute();
    flash("Status pesanan #$idp diubah menjadi \"$sNew\".");
  }
  flash('Status tidak valid.', 'danger');
}

/* ================= DATA TAMPILAN ================= */
$pjs = $db->query("SELECT * FROM penjual")->fetch_all(MYSQLI_ASSOC);
$kts = $db->query("SELECT * FROM kategori")->fetch_all(MYSQLI_ASSOC);
$produk = $db->query("SELECT p.*, k.nama_kategori, s.nama_toko FROM produk p
  LEFT JOIN kategori k ON k.id_kategori=p.id_kategori
  LEFT JOIN penjual s ON s.id_penjual=p.id_penjual
  ORDER BY p.id_produk DESC")->fetch_all(MYSQLI_ASSOC);

$stList = ['baru'=>'primary','diproses'=>'warning','dikirim'=>'info','selesai'=>'success'];
$cnt = array_fill_keys(array_keys($stList), 0);
foreach ($db->query("SELECT status, COUNT(*) c FROM pesanan GROUP BY status") as $r) if (isset($cnt[$r['status']])) $cnt[$r['status']] = (int)$r['c'];
$totPesanan = array_sum($cnt);
$totItem  = (int)$db->query("SELECT COALESCE(SUM(jumlah),0) q FROM detail_pesanan")->fetch_assoc()['q'];
$totOmzet = (float)$db->query("SELECT COALESCE(SUM(total),0) t FROM pesanan")->fetch_assoc()['t'];

$title = 'Admin'; include 'includes/header.php';
?>
<style>
  .pv-box { width:120px; height:120px; border:2px dashed #ced4da; border-radius:12px; overflow:hidden; background:#f8f9fa; display:flex; align-items:center; justify-content:center; color:#6c757d; font-size:.75rem; text-align:center; flex-shrink:0 }
  .pv-box img { width:100%; height:100%; object-fit:cover }
  .thumb { width:64px; height:64px; object-fit:cover; border-radius:10px; flex-shrink:0; cursor:pointer }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="m-0">Dashboard Admin</h3>
  <a href="?logout=1" class="btn btn-sm btn-outline-danger">Logout</a>
</div>

<?php if ($msg): ?><div class="alert alert-<?= e($msgType) ?>"><?= e($msg) ?></div><?php endif; ?>

<datalist id="daftarKategori"><?php foreach ($kts as $r): ?><option value="<?= e($r['nama_kategori']) ?>"><?php endforeach; ?></datalist>

<!-- ============ TAMBAH PRODUK ============ -->
<div class="bg-white p-3 p-md-4 rounded shadow-sm mb-4">
  <h5 class="mb-3">Tambah Produk</h5>
  <form method="post" enctype="multipart/form-data">
    <div class="d-flex flex-column flex-md-row gap-4">
      <div>
        <label class="form-label small text-muted mb-1">Foto produk</label>
        <div class="pv-box mb-2"><span id="addph">Belum ada foto</span><img id="addpv" src="" alt="" style="display:none"></div>
        <input type="file" name="gambar" id="addfile" accept="image/jpeg,image/png,image/webp,image/gif" class="form-control form-control-sm" style="max-width:220px">
        <div class="form-text" id="addmsg">JPG/PNG/WEBP/GIF, maks 2MB</div>
      </div>
      <div class="flex-grow-1">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label small text-muted mb-1">Nama produk</label>
            <input name="nama_produk" class="form-control" value="<?= e($add['nama_produk']) ?>" required></div>
          <div class="col-6 col-md-3"><label class="form-label small text-muted mb-1">Penjual / Toko</label>
            <input name="nama_toko" class="form-control" placeholder="Nama toko" value="<?= e($add['nama_toko']) ?>" required></div>
          <div class="col-6 col-md-3"><label class="form-label small text-muted mb-1">Kategori</label>
            <input name="nama_kategori" class="form-control" list="daftarKategori" placeholder="Ketik / pilih kategori" value="<?= e($add['nama_kategori']) ?>" required autocomplete="off"></div>
          <div class="col-6 col-md-3"><label class="form-label small text-muted mb-1">Harga (Rp)</label>
            <input type="number" step="0.01" min="0" name="harga" class="form-control" value="<?= e($add['harga']) ?>" required></div>
          <div class="col-6 col-md-3"><label class="form-label small text-muted mb-1">Stok</label>
            <input type="number" min="0" name="stok" class="form-control" value="<?= e($add['stok']) ?>" required></div>
          <div class="col-md-6"><label class="form-label small text-muted mb-1">atau URL gambar (opsional)</label>
            <input name="gambar_url" id="addurl" class="form-control" placeholder="https://..." value="<?= e($add['gambar_url']) ?>"></div>
          <div class="col-12"><label class="form-label small text-muted mb-1">Deskripsi</label>
            <textarea name="deskripsi" rows="2" class="form-control"><?= e($add['deskripsi']) ?></textarea></div>
        </div>
        <button name="add" class="btn btn-grad mt-3 px-4">Tambah Produk</button>
      </div>
    </div>
  </form>
</div>

<!-- ============ DAFTAR PRODUK ============ -->
<div class="bg-white p-3 p-md-4 rounded shadow-sm mb-4">
  <h5 class="mb-3">Daftar Produk <span class="badge bg-secondary"><?= count($produk) ?></span></h5>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th>Produk</th><th>Harga</th><th class="text-center">Stok</th><th class="text-end">Aksi</th></tr></thead>
      <tbody>
      <?php if (!$produk): ?><tr><td colspan="4" class="text-center text-muted">Belum ada produk.</td></tr><?php endif; ?>
      <?php foreach ($produk as $p): $pid = (int)$p['id_produk']; ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-3">
              <img class="thumb" src="<?= e(img($p['gambar_url'], $p['nama_produk'])) ?>" alt="" title="Klik untuk ganti foto" onclick="document.getElementById('foto<?= $pid ?>').click()">
              <div><div class="fw-semibold"><?= e($p['nama_produk']) ?></div>
              <small class="text-muted"><?= e($p['nama_kategori'] ?? '-') ?> &middot; <?= e($p['nama_toko'] ?? '-') ?></small></div>
            </div>
          </td>
          <td class="text-nowrap"><?= rp($p['harga']) ?></td>
          <td class="text-center"><span class="badge bg-<?= $p['stok'] > 0 ? 'success' : 'danger' ?>"><?= (int)$p['stok'] ?></span></td>
          <td class="text-end text-nowrap">
            <form method="post" enctype="multipart/form-data" class="d-inline">
              <input type="hidden" name="set_foto" value="1"><input type="hidden" name="id" value="<?= $pid ?>">
              <input type="file" name="gambar" id="foto<?= $pid ?>" class="d-none" accept="image/jpeg,image/png,image/webp,image/gif" onchange="pickFoto(this)">
              <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('foto<?= $pid ?>').click()">Ganti Foto</button>
            </form>
            <button type="button" class="btn btn-sm btn-warning" onclick="toggleEdit(<?= $pid ?>)">Edit</button>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus produk ini?')">
              <input type="hidden" name="id" value="<?= $pid ?>"><button name="delete" class="btn btn-sm btn-danger">Hapus</button>
            </form>
          </td>
        </tr>
        <tr id="edit<?= $pid ?>" class="d-none">
          <td colspan="4" class="bg-light">
            <form method="post" class="row g-2 p-2">
              <input type="hidden" name="id" value="<?= $pid ?>">
              <div class="col-md-4"><label class="small text-muted">Nama produk</label><input name="nama_produk" class="form-control form-control-sm" value="<?= e($p['nama_produk']) ?>" required></div>
              <div class="col-6 col-md-2"><label class="small text-muted">Penjual / Toko</label>
                <input name="nama_toko" class="form-control form-control-sm" value="<?= e($p['nama_toko'] ?? '') ?>" required></div>
              <div class="col-6 col-md-2"><label class="small text-muted">Kategori</label>
                <input name="nama_kategori" class="form-control form-control-sm" list="daftarKategori" value="<?= e($p['nama_kategori'] ?? '') ?>" required autocomplete="off"></div>
              <div class="col-6 col-md-2"><label class="small text-muted">Harga</label><input type="number" step="0.01" min="0" name="harga" class="form-control form-control-sm" value="<?= e($p['harga']) ?>" required></div>
              <div class="col-6 col-md-2"><label class="small text-muted">Stok</label><input type="number" min="0" name="stok" class="form-control form-control-sm" value="<?= (int)$p['stok'] ?>" required></div>
              <div class="col-md-6"><label class="small text-muted">Deskripsi</label><input name="deskripsi" class="form-control form-control-sm" value="<?= e($p['deskripsi']) ?>"></div>
              <div class="col-md-6"><label class="small text-muted">URL gambar (kosongkan jika memakai foto upload)</label>
                <input name="gambar_url" class="form-control form-control-sm" value="<?= e(preg_match('#^https?://#i', (string)$p['gambar_url']) ? $p['gambar_url'] : '') ?>"></div>
              <div class="col-12 d-flex align-items-center gap-3">
                <?php if ($p['gambar_url']): ?><div class="form-check m-0"><input class="form-check-input" type="checkbox" name="hapus_gambar" value="1" id="hp<?= $pid ?>"><label class="form-check-label small" for="hp<?= $pid ?>">Hapus foto</label></div><?php endif; ?>
                <button name="update" class="btn btn-sm btn-grad ms-auto px-3">Simpan</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleEdit(<?= $pid ?>)">Batal</button>
              </div>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ============ PESANAN MASUK ============ -->
<h4 class="mb-3">Pesanan Masuk</h4>
<div class="row g-2 mb-3">
  <div class="col-6 col-md-3"><div class="bg-white p-3 rounded shadow-sm h-100"><div class="text-muted small">Total pesanan</div><div class="fs-4 fw-bold"><?= $totPesanan ?></div></div></div>
  <div class="col-6 col-md-3"><div class="bg-white p-3 rounded shadow-sm h-100"><div class="text-muted small">Total item dipesan</div><div class="fs-4 fw-bold"><?= $totItem ?></div></div></div>
  <div class="col-6 col-md-3"><div class="bg-white p-3 rounded shadow-sm h-100"><div class="text-muted small">Total nilai pesanan</div><div class="fs-4 fw-bold"><?= rp($totOmzet) ?></div></div></div>
  <div class="col-6 col-md-3"><div class="bg-white p-3 rounded shadow-sm h-100"><div class="text-muted small mb-1">Per status</div>
    <?php foreach ($stList as $k => $c): ?><span class="badge bg-<?= $c ?> <?= $c==='warning'||$c==='info' ? 'text-dark' : '' ?>"><?= $k ?>: <?= $cnt[$k] ?></span> <?php endforeach; ?></div></div>
</div>
<div class="bg-white p-3 rounded shadow-sm mb-4">
<div class="table-responsive"><table class="table align-middle mb-0">
<thead><tr><th>#</th><th>Tanggal</th><th>Pembeli</th><th>Item dipesan</th><th class="text-center">Jml</th><th>Total</th><th>Status</th></tr></thead><tbody>
<?php $rs = $db->query("SELECT o.id_pesanan, o.tanggal, o.total, o.status, b.nama, b.telepon,
    COALESCE(SUM(d.jumlah),0) AS jml,
    GROUP_CONCAT(CONCAT(p.nama_produk,' x',d.jumlah) SEPARATOR ', ') AS item
  FROM pesanan o JOIN pembeli b ON b.id_pembeli=o.id_pembeli
  LEFT JOIN detail_pesanan d ON d.id_pesanan=o.id_pesanan
  LEFT JOIN produk p ON p.id_produk=d.id_produk
  GROUP BY o.id_pesanan, o.tanggal, o.total, o.status, b.nama, b.telepon
  ORDER BY o.id_pesanan DESC LIMIT 50");
if ($rs->num_rows === 0): ?><tr><td colspan="7" class="text-center text-muted">Belum ada pesanan.</td></tr><?php endif;
while ($o = $rs->fetch_assoc()): $sc = $stList[$o['status']] ?? 'secondary'; ?>
<tr><td><?= (int)$o['id_pesanan'] ?></td><td class="text-nowrap"><?= e($o['tanggal']) ?></td>
<td><?= e($o['nama']) ?><br><small class="text-muted"><?= e($o['telepon']) ?></small></td>
<td class="small"><?= e($o['item']) ?></td>
<td class="text-center"><span class="badge bg-dark"><?= (int)$o['jml'] ?></span></td>
<td class="text-nowrap"><?= rp($o['total']) ?></td>
<td><form method="post" class="d-flex gap-2 align-items-center"><input type="hidden" name="set_status" value="1"><input type="hidden" name="id_pesanan" value="<?= (int)$o['id_pesanan'] ?>">
<select name="status" class="form-select form-select-sm" style="min-width:120px" onchange="this.form.submit()">
<?php foreach ($stList as $k => $c): ?><option value="<?= $k ?>" <?= $o['status']===$k ? 'selected' : '' ?>><?= ucfirst($k) ?></option><?php endforeach; ?></select>
<span class="badge bg-<?= $sc ?> <?= $sc==='warning'||$sc==='info' ? 'text-dark' : '' ?>"><?= e($o['status']) ?></span></form></td></tr>
<?php endwhile; ?></tbody></table></div>
</div>

<script>
function toggleEdit(id) { document.getElementById('edit' + id).classList.toggle('d-none'); }

// Ganti foto dari daftar: cek ukuran lalu langsung kirim
function pickFoto(inp) {
  var f = inp.files[0]; if (!f) return;
  if (f.size > 2 * 1024 * 1024) { alert('File terlalu besar (maks 2MB).'); inp.value = ''; return; }
  inp.form.submit();
}

// Preview foto di form Tambah Produk
(function () {
  var file = document.getElementById('addfile'), url = document.getElementById('addurl'),
      pv = document.getElementById('addpv'), ph = document.getElementById('addph'), msg = document.getElementById('addmsg');
  function show(src) { pv.src = src; pv.style.display = ''; ph.style.display = 'none'; }
  file.addEventListener('change', function () {
    var f = this.files[0]; if (!f) return;
    if (f.size > 2 * 1024 * 1024) { msg.textContent = 'File terlalu besar (maks 2MB).'; this.value = ''; return; }
    show(URL.createObjectURL(f)); msg.textContent = f.name;
  });
  url.addEventListener('change', function () {
    if (/^https?:\/\//i.test(this.value.trim())) show(this.value.trim());
  });
})();
</script>
<?php include 'includes/footer.php'; ?>