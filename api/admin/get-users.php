<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireAdmin();

$search = trim($_GET['search'] ?? '');
$role   = $_GET['role']        ?? '';
$status = $_GET['status']      ?? '';

$sql    = "SELECT id, name, email, role, status, location, avatar, created_at
           FROM users WHERE 1=1";
$params = [];

if ($search) {
    $sql     .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($role) {
    $sql .= " AND role = ?";
    $params[] = $role;
}
if ($status) {
    $sql .= " AND status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

foreach ($users as &$u) {
    $u['avatar_url']   = $u['avatar']
        ? BASE_URL . 'uploads/avatars/' . $u['avatar']
        : null;
    $u['initial']      = strtoupper(substr($u['name'], 0, 1));
    $u['member_since'] = date('M Y', strtotime($u['created_at']));

    // Count their listings
    $ls = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ?");
    $ls->execute([$u['id']]);
    $u['listing_count'] = (int)$ls->fetchColumn();
}

jsonResponse(['success' => true, 'users' => $users]);
