<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Fetch all categories with their parent name
$stmt = $pdo->query("
    SELECT c.id, c.name, c.parent_id, p.name AS parent_name
    FROM categories c
    LEFT JOIN categories p ON c.parent_id = p.id
    ORDER BY COALESCE(c.parent_id, c.id), c.id
");

$categories = $stmt->fetchAll();
jsonResponse(['success' => true, 'categories' => $categories]);
