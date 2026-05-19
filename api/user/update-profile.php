<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed.'], 405);
}

$userId   = (int)$_SESSION['user_id'];
$name     = sanitize($_POST['name']     ?? '');
$location = sanitize($_POST['location'] ?? '');
$email    = trim($_POST['email']        ?? '');

if (!$name)  jsonResponse(['error' => 'Name is required.'], 422);
if (!$email) jsonResponse(['error' => 'Email is required.'], 422);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Invalid email address.'], 422);
}

// Check email not taken by another user
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->execute([$email, $userId]);
if ($stmt->fetch()) {
    jsonResponse(['error' => 'That email is already used by another account.'], 409);
}

// Update profile
$stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, location = ? WHERE id = ?");
$stmt->execute([$name, $email, $location ?: null, $userId]);

// Update session
$_SESSION['name']  = $name;
$_SESSION['email'] = $email;

// Handle password change (optional)
$currentPw = $_POST['current_password'] ?? '';
$newPw     = $_POST['new_password']     ?? '';

if ($currentPw && $newPw) {
    if (strlen($newPw) < 6) {
        jsonResponse(['error' => 'New password must be at least 6 characters.'], 422);
    }
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    if (!password_verify($currentPw, $row['password'])) {
        jsonResponse(['error' => 'Current password is incorrect.'], 401);
    }

    $hash = password_hash($newPw, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hash, $userId]);
}

jsonResponse(['success' => true, 'message' => 'Profile updated successfully.']);
