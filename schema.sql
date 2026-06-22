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

-- ===================================================================
-- TABEL SINKRONISASI — ditambahkan oleh sistem sync service
-- Digunakan untuk menyimpan cache data dari Swagger/Ginee API
-- ===================================================================

-- Ringkasan order (dari /orders/count)
CREATE TABLE IF NOT EXISTS sync_order_summary (
  id INT PRIMARY KEY DEFAULT 1,
  total_order INT DEFAULT 0,
  total_valid_order INT DEFAULT 0,
  total_valid_amount DECIMAL(15,2) DEFAULT 0,
  total_amount DECIMAL(15,2) DEFAULT 0,
  total_cancel_order INT DEFAULT 0,
  total_cancel_amount DECIMAL(15,2) DEFAULT 0,
  total_valid_quantity INT DEFAULT 0,
  synced_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order dari marketplace (dari /orders)
CREATE TABLE IF NOT EXISTS sync_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  external_order_id VARCHAR(100) NOT NULL,
  order_status VARCHAR(50) DEFAULT NULL,
  payment_method VARCHAR(100) DEFAULT NULL,
  channel_id VARCHAR(50) DEFAULT NULL,
  customer_name VARCHAR(255) DEFAULT NULL,
  customer_mobile VARCHAR(50) DEFAULT NULL,
  external_create_datetime DATETIME DEFAULT NULL,
  total_quantity INT DEFAULT 0,
  total_amount DECIMAL(15,2) DEFAULT 0,
  raw_json LONGTEXT DEFAULT NULL,
  first_synced_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_synced_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_external_order_id (external_order_id),
  INDEX idx_order_status (order_status),
  INDEX idx_channel_id (channel_id),
  INDEX idx_create_datetime (external_create_datetime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Item dalam order marketplace
CREATE TABLE IF NOT EXISTS sync_order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  external_order_id VARCHAR(100) DEFAULT NULL,
  product_name VARCHAR(255) DEFAULT NULL,
  quantity INT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_order_id (order_id),
  INDEX idx_external_order_id (external_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Produk dari API eksternal (dari /product)
CREATE TABLE IF NOT EXISTS sync_products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  external_product_id VARCHAR(100) DEFAULT NULL,
  product_code VARCHAR(100) DEFAULT NULL,
  product_name VARCHAR(255) DEFAULT NULL,
  category VARCHAR(100) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  unit VARCHAR(20) DEFAULT 'PCS',
  price DECIMAL(15,2) DEFAULT 0,
  stock INT DEFAULT 0,
  status VARCHAR(20) DEFAULT 'active',
  raw_json LONGTEXT DEFAULT NULL,
  first_synced_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_synced_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_external_product_id (external_product_id),
  INDEX idx_product_code (product_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log eksekusi sinkronisasi
CREATE TABLE IF NOT EXISTS sync_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sync_type VARCHAR(50) NOT NULL,
  status ENUM('running','success','failed') NOT NULL DEFAULT 'running',
  started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  finished_at DATETIME DEFAULT NULL,
  records_inserted INT DEFAULT 0,
  records_updated INT DEFAULT 0,
  records_failed INT DEFAULT 0,
  total_records INT DEFAULT 0,
  pages_fetched INT DEFAULT 0,
  error_message TEXT DEFAULT NULL,
  INDEX idx_sync_type (sync_type),
  INDEX idx_started_at (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
