<?php
// Validates credentials and start session 
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

if (!$email || !$password) {
    jsonResponse(['error' => 'Email and password are required.'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Invalid email address.'], 422);
}

// Fetch user by email
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    jsonResponse(['error' => 'Incorrect email or password.'], 401);
}

if ($user['status'] === 'blocked') {
    jsonResponse(['error' => 'Your account has been blocked. Contact support.'], 403);
}

// Start session
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user_id'] = $user['id'];
$_SESSION['name']    = $user['name'];
$_SESSION['email']   = $user['email'];
$_SESSION['role']    = $user['role'];

// RBAC redirect URLs
$redirectMap = [
    'admin'  => BASE_URL . '../admin/dashboard.php',
    'user'  => BASE_URL . 'index.php',
];

jsonResponse([
    'success'  => true,
    'role'     => $user['role'],
    'redirect' => $redirectMap[$user['role']] ?? BASE_URL . 'index.php',
]);
