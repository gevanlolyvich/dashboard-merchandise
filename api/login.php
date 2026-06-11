<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (!$username || !$password) {
  http_response_code(400);
  echo json_encode(['error' => 'Username dan password wajib diisi']);
  exit;
}

$stmt = $pdo->prepare("SELECT id, username, password, role, display_name FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Username atau password salah']);
  exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['display_name'] = $user['display_name'];

echo json_encode([
  'success' => true,
  'user' => [
    'username' => $user['username'],
    'role' => $user['role'],
    'display_name' => $user['display_name'],
  ]
]);
