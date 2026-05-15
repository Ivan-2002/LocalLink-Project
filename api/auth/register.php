<?php
// Create user, hashes password, and auto-logins 
// api/auth/register.php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$name     = sanitize($_POST['name']     ?? '');
$email    = trim($_POST['email']        ?? '');
$password = $_POST['password']          ?? '';
$role     = $_POST['role']              ?? '';
$location = sanitize($_POST['location'] ?? '');

// Validate
if (!$name || !$email || !$password || !$role) {
    jsonResponse(['error' => 'All required fields must be filled.'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Invalid email address.'], 422);
}

if (strlen($password) < 6) {
    jsonResponse(['error' => 'Password must be at least 6 characters.'], 422);
}

if (!in_array($role, ['buyer', 'seller'])) {
    jsonResponse(['error' => 'Invalid role selected.'], 422);
}

// Check email already taken
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonResponse(['error' => 'An account with this email already exists.'], 409);
}

// Insert user
$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare(
    "INSERT INTO users (name, email, password, role, location) VALUES (?, ?, ?, ?, ?)"
);
$stmt->execute([$name, $email, $hash, $role, $location ?: null]);
$userId = $pdo->lastInsertId();

// Auto-login after register
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user_id'] = $userId;
$_SESSION['name']    = $name;
$_SESSION['email']   = $email;
$_SESSION['role']    = $role;

$redirectMap = [
    'seller' => BASE_URL . 'seller/dashboard.php',
    'buyer'  => BASE_URL . 'index.php',
];

jsonResponse([
    'success'  => true,
    'role'     => $role,
    'redirect' => $redirectMap[$role],
]);
