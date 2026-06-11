<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
  echo json_encode([
    'logged_in' => true,
    'user' => [
      'username' => $_SESSION['username'],
      'role' => $_SESSION['role'],
      'display_name' => $_SESSION['display_name'],
    ]
  ]);
} else {
  echo json_encode(['logged_in' => false]);
}
