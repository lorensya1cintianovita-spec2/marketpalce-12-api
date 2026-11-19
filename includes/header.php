<?php $isAdminPage = basename($_SERVER['SCRIPT_NAME']) === 'admin.php'; ?>
<!doctype html><html lang="id"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? 'Marketplace') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="assets/style.css?v=<?= @filemtime(__DIR__ . '/../assets/style.css') ?>" rel="stylesheet">
</head><body>
<nav class="navbar navbar-expand navbar-dark mb-4"><div class="container">
<a class="navbar-brand" href="index.php"><i class="bi bi-bag-heart-fill"></i> Mini Marketplace</a>
<div class="navbar-nav ms-auto">
<?php if (!$isAdminPage): ?>
  <a class="nav-link" href="pesanan.php"><i class="bi bi-receipt"></i> Pesanan</a>
  <a class="nav-link" href="cart.php"><i class="bi bi-cart3"></i> Keranjang <span class="badge rounded-pill bg-light text-dark"><?= cart_count() ?></span></a>
<?php endif; ?>
<a class="nav-link" href="admin.php"><i class="bi bi-person-gear"></i> Admin</a></div></div></nav>
<div class="container">
