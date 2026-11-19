<?php
session_start();
require __DIR__ . '/../config.php';
mysqli_report(MYSQLI_REPORT_OFF);
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) die('Koneksi database gagal.');
$db->set_charset('utf8mb4');
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rp($n){ return 'Rp ' . number_format($n, 0, ',', '.'); }
function img($u, $n=''){
  if ($u) return preg_match('#^https?://#', $u) ? $u : 'uploads/' . $u;
  $t = strtoupper(mb_substr($n ?: 'P', 0, 1));
  $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#6366f1"/><stop offset="1" stop-color="#ec4899"/></linearGradient></defs><rect width="400" height="300" fill="url(#g)"/><text x="200" y="185" font-size="120" font-family="Arial" font-weight="bold" fill="#fff" fill-opacity=".85" text-anchor="middle">' . htmlspecialchars($t) . '</text></svg>';
  return 'data:image/svg+xml;base64,' . base64_encode($svg);
}
function cart_count(){ return array_sum($_SESSION['cart'] ?? []); }
