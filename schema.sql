-- Schema: dashboard_merchandise v2
-- Arsitektur Master Stok + Mutasi Stok + Multi Channel Sales

CREATE DATABASE IF NOT EXISTS dashboard_merchandise
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE dashboard_merchandise;

-- ===== USERS =====
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  display_name VARCHAR(100) NOT NULL,
  role ENUM('superadmin', 'admin', 'user') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO users (username, password, display_name, role)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'superadmin');

-- ===== MASTER PRODUK =====
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_code VARCHAR(50) NOT NULL UNIQUE,
  product_name VARCHAR(255) NOT NULL,
  category VARCHAR(100) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  unit VARCHAR(20) NOT NULL DEFAULT 'PCS',
  current_stock INT NOT NULL DEFAULT 0,
  status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===== PEMASUKAN STOK =====
CREATE TABLE IF NOT EXISTS stock_in (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_code VARCHAR(50) NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  unit VARCHAR(20) NOT NULL DEFAULT 'PCS',
  source VARCHAR(100) DEFAULT NULL,
  supplier VARCHAR(255) DEFAULT NULL,
  reference_number VARCHAR(100) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  received_date DATE NOT NULL,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===== RIWAYAT MUTASI STOK =====
-- Semua pergerakan stok dicatat di sini
CREATE TABLE IF NOT EXISTS stock_mutations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_code VARCHAR(50) NOT NULL,
  mutation_type ENUM('masuk', 'opd', 'bumd', 'marketplace', 'pos', 'penyesuaian', 'refund') NOT NULL,
  quantity INT NOT NULL,
  unit VARCHAR(20) NOT NULL DEFAULT 'PCS',
  reference_type VARCHAR(50) DEFAULT NULL,
  reference_id INT DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===== PENYESUAIAN STOK =====
CREATE TABLE IF NOT EXISTS stock_adjustments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_code VARCHAR(50) NOT NULL,
  adjustment_type ENUM('plus', 'minus') NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  reason TEXT NOT NULL,
  notes TEXT DEFAULT NULL,
  adjusted_date DATE NOT NULL,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===== MASTER OPD =====
CREATE TABLE IF NOT EXISTS opd_customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  address TEXT DEFAULT NULL,
  pic_name VARCHAR(255) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  email VARCHAR(255) DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===== MASTER BUMD =====
CREATE TABLE IF NOT EXISTS bumd_customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  address TEXT DEFAULT NULL,
  pic_name VARCHAR(255) DEFAULT NULL,
  phone VARCHAR(50) DEFAULT NULL,
  email VARCHAR(255) DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===== PENJUALAN OPD =====
CREATE TABLE IF NOT EXISTS sales_opd (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_number VARCHAR(50) NOT NULL UNIQUE,
  opd_id INT NOT NULL,
  transaction_date DATE NOT NULL,
  status ENUM('draft', 'diproses', 'selesai', 'dibatalkan', 'refund') NOT NULL DEFAULT 'draft',
  notes TEXT DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (opd_id) REFERENCES opd_customers(id) ON DELETE RESTRICT,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales_opd_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sales_opd_id INT NOT NULL,
  product_code VARCHAR(50) NOT NULL,
  product_name VARCHAR(255) NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  unit VARCHAR(20) NOT NULL DEFAULT 'PCS',
  price DECIMAL(15,2) NOT NULL DEFAULT 0,
  total DECIMAL(15,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (sales_opd_id) REFERENCES sales_opd(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== REKAP MERCHANDISE JFF =====
CREATE TABLE IF NOT EXISTS merchandise_jff (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode_barang VARCHAR(50) DEFAULT NULL,
  tipe_kategori VARCHAR(100) DEFAULT NULL,
  nama_barang VARCHAR(255) DEFAULT NULL,
  ukuran_varian VARCHAR(100) DEFAULT NULL,
  satuan VARCHAR(20) DEFAULT NULL,
  hpp DECIMAL(15,2) DEFAULT 0,
  harga_ritel DECIMAL(15,2) DEFAULT 0,
  harga_institusi DECIMAL(15,2) DEFAULT 0,
  margin DECIMAL(5,2) DEFAULT 0,
  stok_awal INT DEFAULT 0,
  barang_masuk INT DEFAULT 0,
  barang_terjual INT DEFAULT 0,
  day_1_jff INT DEFAULT 0,
  stok_akhir INT DEFAULT 0,
  pendapatan DECIMAL(15,2) DEFAULT 0,
  produksi INT DEFAULT 0,
  day_2 INT DEFAULT 0,
  pendapatan_2 DECIMAL(15,2) DEFAULT 0,
  day_3 INT DEFAULT 0,
  pendapatan_3 DECIMAL(15,2) DEFAULT 0,
  import_date DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ===== PENJUALAN BUMD =====
CREATE TABLE IF NOT EXISTS sales_bumd (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_number VARCHAR(50) NOT NULL UNIQUE,
  bumd_id INT NOT NULL,
  transaction_date DATE NOT NULL,
  status ENUM('draft', 'diproses', 'selesai', 'dibatalkan', 'refund') NOT NULL DEFAULT 'draft',
  notes TEXT DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (bumd_id) REFERENCES bumd_customers(id) ON DELETE RESTRICT,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales_bumd_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sales_bumd_id INT NOT NULL,
  product_code VARCHAR(50) NOT NULL,
  product_name VARCHAR(255) NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  unit VARCHAR(20) NOT NULL DEFAULT 'PCS',
  price DECIMAL(15,2) NOT NULL DEFAULT 0,
  total DECIMAL(15,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (sales_bumd_id) REFERENCES sales_bumd(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===== REFUND PRODUK =====
CREATE TABLE IF NOT EXISTS refunds (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_type VARCHAR(10) NOT NULL,
  transaction_id INT NOT NULL,
  transaction_number VARCHAR(50) DEFAULT NULL,
  customer_name VARCHAR(255) DEFAULT NULL,
  refund_date DATE NOT NULL,
  notes TEXT DEFAULT NULL,
  created_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS refund_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  refund_id INT NOT NULL,
  product_code VARCHAR(50) NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  unit VARCHAR(20) NOT NULL DEFAULT 'PCS',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (refund_id) REFERENCES refunds(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Drop old tables (migrated to new schema)
DROP TABLE IF EXISTS inventory_in;
DROP TABLE IF EXISTS inventory_out;
