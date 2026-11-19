-- Import lewat phpMyAdmin pada database yang sudah dibuat di panel InfinityFree
CREATE TABLE penjual (
  id_penjual INT AUTO_INCREMENT PRIMARY KEY,
  nama_toko VARCHAR(100) NOT NULL,
  email VARCHAR(100),
  telepon VARCHAR(20)
) ENGINE=InnoDB;
CREATE TABLE kategori (
  id_kategori INT AUTO_INCREMENT PRIMARY KEY,
  nama_kategori VARCHAR(50) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE produk (
  id_produk INT AUTO_INCREMENT PRIMARY KEY,
  id_penjual INT NOT NULL,
  id_kategori INT NOT NULL,
  nama_produk VARCHAR(150) NOT NULL,
  deskripsi TEXT,
  harga DECIMAL(12,2) NOT NULL,
  stok INT NOT NULL DEFAULT 0,
  gambar_url VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_penjual) REFERENCES penjual(id_penjual),
  FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori)
) ENGINE=InnoDB;
CREATE TABLE pembeli (
  id_pembeli INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  telepon VARCHAR(20) NOT NULL,
  alamat TEXT NOT NULL
) ENGINE=InnoDB;
CREATE TABLE pesanan (
  id_pesanan INT AUTO_INCREMENT PRIMARY KEY,
  id_pembeli INT NOT NULL,
  tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
  total DECIMAL(12,2) NOT NULL,
  status ENUM('baru','diproses','dikirim','selesai') DEFAULT 'baru',
  FOREIGN KEY (id_pembeli) REFERENCES pembeli(id_pembeli)
) ENGINE=InnoDB;
CREATE TABLE detail_pesanan (
  id_detail INT AUTO_INCREMENT PRIMARY KEY,
  id_pesanan INT NOT NULL,
  id_produk INT NOT NULL,
  jumlah INT NOT NULL,
  harga_satuan DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan) ON DELETE CASCADE,
  FOREIGN KEY (id_produk) REFERENCES produk(id_produk)
) ENGINE=InnoDB;

INSERT INTO penjual (nama_toko,email,telepon) VALUES ('Toko Demo','demo@example.com','08123456789');
INSERT INTO kategori (nama_kategori) VALUES ('Elektronik'),('Fashion'),('Makanan');
INSERT INTO produk (id_penjual,id_kategori,nama_produk,deskripsi,harga,stok,gambar_url) VALUES
(1,1,'Headphone Bluetooth','Headphone nirkabel dengan baterai tahan 20 jam.',250000,15,''),
(1,2,'Kaos Polos Katun','Kaos katun combed 30s, nyaman dipakai.',65000,40,''),
(1,3,'Kopi Bengkulu 250g','Kopi robusta asli Bengkulu, biji sangrai.',45000,25,'');
