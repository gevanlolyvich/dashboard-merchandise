<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
  http_response_code(403);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

require_once __DIR__ . '/db.php';

$method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];

// ---- GET: List all users or single user ----
if ($method === 'GET') {
  $getId = (int)($_GET['id'] ?? 0);
  if ($getId > 0) {
    $stmt = $pdo->prepare("SELECT id, username, display_name, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$getId]);
    $user = $stmt->fetch();
    if (!$user) {
      http_response_code(404);
      echo json_encode(['error' => 'User tidak ditemukan']);
      exit;
    }
    echo json_encode(['item' => $user]);
    exit;
  }
  $stmt = $pdo->query("SELECT id, username, display_name, role, created_at FROM users ORDER BY id ASC");
  $users = $stmt->fetchAll();
  echo json_encode(['users' => $users]);
  exit;
}

// ---- POST: Create user ----
if ($method === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = trim($_POST['password'] ?? '');
  $displayName = trim($_POST['display_name'] ?? '');
  $role = trim($_POST['role'] ?? 'user');

  if (!$username || !$displayName) {
    http_response_code(400);
    echo json_encode(['error' => 'Username dan Nama tampilan wajib diisi']);
    exit;
  }

  if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'Password minimal 6 karakter']);
    exit;
  }

  if (!in_array($role, ['admin', 'user'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Role tidak valid']);
    exit;
  }

  $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
  $stmt->execute([$username]);
  if ($stmt->fetchColumn() > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'Username sudah digunakan']);
    exit;
  }

  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("INSERT INTO users (username, password, role, display_name) VALUES (?, ?, ?, ?)");
  $stmt->execute([$username, $hash, $role, $displayName]);

  echo json_encode(['success' => true, 'message' => "User '$username' berhasil dibuat"]);
  exit;
}

// ---- PUT: Update user ----
if ($method === 'PUT') {
  $id = (int)($_POST['id'] ?? 0);
  $username = trim($_POST['username'] ?? '');
  $password = trim($_POST['password'] ?? '');
  $displayName = trim($_POST['display_name'] ?? '');
  $role = trim($_POST['role'] ?? '');

  if (!$id || !$username || !$displayName) {
    http_response_code(400);
    echo json_encode(['error' => 'Data tidak lengkap']);
    exit;
  }

  // Check if target user is superadmin
  $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
  $stmt->execute([$id]);
  $target = $stmt->fetch();
  if (!$target) {
    http_response_code(404);
    echo json_encode(['error' => 'User tidak ditemukan']);
    exit;
  }

  $isTargetSuper = $target['role'] === 'superadmin';

  if ($isTargetSuper) {
    // Superadmin can only change password
    if (!$password) {
      http_response_code(400);
      echo json_encode(['error' => 'Password wajib diisi untuk Superadmin']);
      exit;
    }
    if (strlen($password) < 6) {
      http_response_code(400);
      echo json_encode(['error' => 'Password minimal 6 karakter']);
      exit;
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->execute([$hash, $id]);
    echo json_encode(['success' => true, 'message' => 'Password Superadmin berhasil diperbarui']);
    exit;
  }

  if (!in_array($role, ['admin', 'user'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Role tidak valid']);
    exit;
  }

  $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
  $stmt->execute([$username, $id]);
  if ($stmt->fetchColumn() > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'Username sudah digunakan user lain']);
    exit;
  }

  if ($password) {
    if (strlen($password) < 6) {
      http_response_code(400);
      echo json_encode(['error' => 'Password minimal 6 karakter']);
      exit;
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET username=?, password=?, role=?, display_name=? WHERE id=?");
    $stmt->execute([$username, $hash, $role, $displayName, $id]);
  } else {
    $stmt = $pdo->prepare("UPDATE users SET username=?, role=?, display_name=? WHERE id=?");
    $stmt->execute([$username, $role, $displayName, $id]);
  }

  echo json_encode(['success' => true, 'message' => "User '$username' berhasil diperbarui"]);
  exit;
}

// ---- DELETE: Delete user ----
if ($method === 'DELETE') {
  $id = (int)($_GET['id'] ?? 0);

  if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID tidak valid']);
    exit;
  }

  if ($id === (int)$_SESSION['user_id']) {
    http_response_code(400);
    echo json_encode(['error' => 'Tidak dapat menghapus akun sendiri']);
    exit;
  }

  $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
  $stmt->execute([$id]);
  $target = $stmt->fetch();
  if (!$target) {
    http_response_code(404);
    echo json_encode(['error' => 'User tidak ditemukan']);
    exit;
  }
  if ($target['role'] === 'superadmin') {
    http_response_code(400);
    echo json_encode(['error' => 'Tidak dapat menghapus Superadmin']);
    exit;
  }

  $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
  $stmt->execute([$id]);

  if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'User tidak ditemukan']);
    exit;
  }

  echo json_encode(['success' => true, 'message' => 'User berhasil dihapus']);
  exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
