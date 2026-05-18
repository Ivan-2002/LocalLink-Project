<?php
// isLoggedIn(), isAdmin(), and isSeller() helpers
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function getRole(): string
{
    return $_SESSION['role'] ?? '';
}

function isAdmin(): bool
{
    return getRole() === 'admin';
}

/**
 * Redirect to login if not logged in.
 * Optionally restrict to a specific role.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect(BASE_URL . 'login.php');
    }
}

function requireAdmin(): void
{
    if (!isLoggedIn() || !isAdmin()) {
        // Send back to admin login
        header('Location: ' . rtrim(BASE_URL, '/') . '/../admin/index.php');
        exit;
    }
}

function redirect(string $url): void
{
    header("Location: $url");
    exit;
}
