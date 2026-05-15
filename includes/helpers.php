<?php
// includes/helpers.php
// Shared utility functions used across the entire project
// Always loaded after config.php

// ── sanitize() ───────────────────────────────────────────────
// Cleans user input before displaying it on screen.
// Prevents XSS attacks (someone injecting HTML/JS into your page).
//
// Usage:  echo sanitize($_POST['name']);
//
function sanitize(string $data): string
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// ── redirect() ───────────────────────────────────────────────
// Sends the user to another page and stops the script.
//
// Usage:  redirect(BASE_URL . 'login.php');
//
// function redirect(string $url): void
// {
//     header("Location: $url");
//     exit;
// }

// ── jsonResponse() ───────────────────────────────────────────
// Sends a JSON response back to jQuery AJAX calls.
// Always call this in your api/ files instead of echo.
//
// Usage:  jsonResponse(['success' => true, 'data' => $rows]);
//         jsonResponse(['error' => 'Not found.'], 404);
//
function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ── uploadImage() ────────────────────────────────────────────
// Handles a product image upload safely.
// Returns the saved filename on success, or false on failure.
//
// Usage:
//   $filename = uploadImage($_FILES['image']);
//   if (!$filename) { jsonResponse(['error' => 'Invalid image.']); }
//
function uploadImage(array $file): string|false
{
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];

    // Check file type
    if (!in_array($file['type'], $allowed)) {
        return false;
    }

    // Check file size (MAX_FILE_SIZE defined in config.php)
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }

    // Generate a unique filename so files never overwrite each other
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('prod_', true) . '.' . $ext;
    $dest     = UPLOAD_DIR . $filename;

    // Move from PHP temp folder to your uploads folder
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return $filename;
    }

    return false;
}
