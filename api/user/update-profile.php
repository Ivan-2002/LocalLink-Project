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
    jsonResponse(['error' => 'That email is already in use.'], 409);
}

// ── Avatar upload ─────────────────────────────────────────────
$avatarName = null; // null means keep existing

if (!empty($_FILES['avatar']['name'])) {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $file    = $_FILES['avatar'];

    if (!in_array($file['type'], $allowed)) {
        jsonResponse(['error' => 'Avatar must be JPG, PNG or WebP.'], 422);
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        jsonResponse(['error' => 'Avatar must be under 2MB.'], 422);
    }

    // Ensure avatars folder exists
    $avatarDir = UPLOAD_DIR . 'avatars/';
    if (!is_dir($avatarDir)) mkdir($avatarDir, 0755, true);

    // Delete old avatar
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $old = $stmt->fetchColumn();
    if ($old && file_exists($avatarDir . $old)) {
        unlink($avatarDir . $old);
    }

    // Save new avatar
    $ext        = pathinfo($file['name'], PATHINFO_EXTENSION);
    $avatarName = 'avatar_' . $userId . '_' . uniqid() . '.' . $ext;
    move_uploaded_file($file['tmp_name'], $avatarDir . $avatarName);
}

// ── Update profile ────────────────────────────────────────────
if ($avatarName) {
    // Update with new avatar
    $stmt = $pdo->prepare("
        UPDATE users SET name = ?, email = ?, location = ?, avatar = ?
        WHERE id = ?
    ");
    $stmt->execute([$name, $email, $location ?: null, $avatarName, $userId]);
} else {
    // No avatar change
    $stmt = $pdo->prepare("
        UPDATE users SET name = ?, email = ?, location = ?
        WHERE id = ?
    ");
    $stmt->execute([$name, $email, $location ?: null, $userId]);
}

// Update session
$_SESSION['name']  = $name;
$_SESSION['email'] = $email;
if ($avatarName) {
    $_SESSION['avatar'] = $avatarName;
}

// ── Password change (optional) ────────────────────────────────
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
    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $userId]);
}

// Return new avatar URL so JS can update the page live
$avatarUrl = null;
if ($avatarName) {
    $avatarUrl = BASE_URL . 'uploads/avatars/' . $avatarName;
} else {
    // Return existing if there is one
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $existing = $stmt->fetchColumn();
    if ($existing) $avatarUrl = BASE_URL . 'uploads/avatars/' . $existing;
}

jsonResponse([
    'success'    => true,
    'message'    => 'Profile updated successfully.',
    'avatar_url' => $avatarUrl,
]);
