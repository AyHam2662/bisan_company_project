<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$productId = (int)($_GET['id'] ?? 0);
$product = $productId ? db_fetch_one('SELECT p.*, c.name AS category_name FROM product p LEFT JOIN category c ON c.category_id = p.category_id WHERE p.product_id = ?', [$productId]) : null;
if (!$product) {
    send_json_error('المنتج غير موجود.', 404);
}

$arabicBrandNames = ['Bisan' => 'بيسان', 'Gree' => 'جري', 'LG' => 'إل جي', 'Daikin' => 'دايكن', 'Midea' => 'ميديا', 'Samsung' => 'سامسونج', 'Carrier' => 'كارير'];
$specs = parse_specs($product['specs']);
$warrantyNote = '';
foreach ($specs as [$specName, $specValue]) {
    if (str_contains($specName, 'الضمان')) {
        $warrantyNote = 'شامل الضريبة وضمان ' . $specValue;
    }
}
$similar = db_fetch_all('SELECT * FROM product WHERE product_id <> ? ORDER BY (category_id <=> ?) DESC, is_available DESC, product_id LIMIT 4', [$productId, $product['category_id']]);

send_json([
    'ok' => true,
    'product' => product_to_array($product) + [
        'brand_label'   => $product['brand'] . (isset($arabicBrandNames[$product['brand']]) ? ' (' . $arabicBrandNames[$product['brand']] . ')' : ''),
        'description'   => $product['description'],
        'category_id'   => (int)$product['category_id'],
        'category_name' => $product['category_name'],
        'category_url'  => url('products.html?category[]=' . (int)$product['category_id']),
        'specs'         => $specs,
        'warranty_note' => $warrantyNote,
        'order_url'     => url('maintenance.html?product=' . $productId . '&service=1'),
    ],
    'similar' => array_map('product_to_array', $similar),
]);
