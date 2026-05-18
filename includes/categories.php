<?php
// Helper class
// include this wherever I need a category list
// e.g. product forms, filter sidebars, admin pages

function getAllCategories(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT c.id, c.name, c.parent_id, p.name AS parent_name
        FROM categories c
        LEFT JOIN categories p ON c.parent_id = p.id
        ORDER BY COALESCE(c.parent_id, c.id), c.id
    ");
    return $stmt->fetchAll();
}

/**
 * Build an <option> list for a <select> dropdown.
 * Groups subcategories under their parent.
 * Pass $selected to pre-select a value (for edit forms).
 */
function categoryOptions(PDO $pdo, ?int $selected = null, bool $includeBlank = true): string
{
    $categories = getAllCategories($pdo);

    // Separate parents and children
    $parents  = array_filter($categories, fn($c) => is_null($c['parent_id']));
    $children = array_filter($categories, fn($c) => !is_null($c['parent_id']));

    $html = $includeBlank ? '<option value="">-- Select category --</option>' : '';

    foreach ($parents as $parent) {
        $sel  = ($selected === (int)$parent['id']) ? ' selected' : '';
        $html .= "<option value=\"{$parent['id']}\"$sel>{$parent['name']}</option>";

        // Add any children under this parent as indented options
        foreach ($children as $child) {
            if ((int)$child['parent_id'] === (int)$parent['id']) {
                $sel  = ($selected === (int)$child['id']) ? ' selected' : '';
                $html .= "<option value=\"{$child['id']}\"$sel>&nbsp;&nbsp;&nbsp;↳ {$child['name']}</option>";
            }
        }
    }

    return $html;
}
