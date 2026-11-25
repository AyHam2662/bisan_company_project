<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$perPage = 9;
$where = [];
$params = [];

$searchText = trim((string)($_GET['q'] ?? ''));
if ($searchText !== '') {
    $where[] = '(p.name LIKE ? OR p.brand LIKE ? OR p.model LIKE ?)';
    $like = '%' . $searchText . '%';
    array_push($params, $like, $like, $like);
}

$categoryIds = array_values(array_filter(array_map('intval', (array)($_GET['category'] ?? []))));
if ($categoryIds) {
    $where[] = 'p.category_id IN (' . implode(',', array_fill(0, count($categoryIds), '?')) . ')';
    array_push($params, ...$categoryIds);
}

$brands = array_values(array_filter(array_map(fn($brand) => mb_substr(trim((string)$brand), 0, 80), (array)($_GET['brand'] ?? []))));
if ($brands) {
    $where[] = 'p.brand IN (' . implode(',', array_fill(0, count($brands), '?')) . ')';
    array_push($params, ...$brands);
}

if (isset($_GET['min']) && $_GET['min'] !== '' && is_numeric($_GET['min'])) {
    $where[] = 'p.price >= ?';
    $params[] = (float)$_GET['min'];
}
if (isset($_GET['max']) && $_GET['max'] !== '' && is_numeric($_GET['max'])) {
    $where[] = 'p.price <= ?';
    $params[] = (float)$_GET['max'];
}
if (($_GET['available'] ?? '') === '1') {
    $where[] = 'p.is_available = 1';
}

$order = match ($_GET['sort'] ?? '') {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'newest'     => 'p.created_at DESC, p.product_id DESC',
    default      => 'p.is_featured DESC, p.is_available DESC, p.product_id ASC',
};

$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$total = (int)db_fetch_value("SELECT COUNT(*) FROM product p $sqlWhere", $params);
$pages = max(1, (int)ceil($total / $perPage));
$page = min($pages, max(1, (int)($_GET['page'] ?? 1)));
$offset = ($page - 1) * $perPage;

$rows = db_fetch_all("SELECT p.product_id, p.name, p.brand, p.model, p.price, p.is_available, p.image, c.name AS category
               FROM product p LEFT JOIN category c ON c.category_id = p.category_id
               $sqlWhere ORDER BY $order LIMIT $perPage OFFSET $offset", $params);

$items = array_map('product_to_array', $rows);

send_json(['ok' => true, 'items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages]);
