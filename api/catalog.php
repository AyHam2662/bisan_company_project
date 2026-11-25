<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$sections = array_filter(explode(',', (string)($_GET['include'] ?? '')));
$out = ['ok' => true];
$arabicBrandNames = ['Bisan' => 'بيسان', 'Gree' => 'جري', 'LG' => 'إل جي', 'Daikin' => 'دايكن', 'Midea' => 'ميديا', 'Samsung' => 'سامسونج', 'Carrier' => 'كارير'];

if (in_array('categories', $sections, true)) {
    $out['categories'] = array_map(fn($category) => [
        'id'          => (int)$category['category_id'],
        'name'        => $category['name'],
        'description' => $category['description'],
        'icon'        => $category['icon'],
        'url'         => url('products.html?category[]=' . (int)$category['category_id']),
    ], db_fetch_all('SELECT * FROM category ORDER BY sort_order, category_id'));
}

if (in_array('featured', $sections, true)) {
    $out['featured'] = array_map('product_to_array', db_fetch_all('SELECT * FROM product ORDER BY is_featured DESC, product_id LIMIT 4'));
}

if (in_array('services', $sections, true)) {
    $out['services'] = array_map('service_to_array', db_fetch_all('SELECT * FROM service WHERE is_active = 1 ORDER BY sort_order, service_id'));
}

if (in_array('brands', $sections, true)) {
    $out['brands'] = array_map(fn($brand) => [
        'name'  => $brand['brand'],
        'label' => $brand['brand'] . (isset($arabicBrandNames[$brand['brand']]) ? ' (' . $arabicBrandNames[$brand['brand']] . ')' : ''),
    ], db_fetch_all('SELECT brand FROM product GROUP BY brand ORDER BY brand'));
    $range = db_fetch_one('SELECT FLOOR(MIN(price)) mn, CEIL(MAX(price)) mx FROM product') ?: ['mn' => 0, 'mx' => 0];
    $out['price_range'] = ['min' => (int)$range['mn'], 'max' => (int)$range['mx']];
}

if (!empty($_GET['product'])) {
    $product = db_fetch_one('SELECT product_id, name FROM product WHERE product_id = ?', [(int)$_GET['product']]);
    $out['product'] = $product ? ['id' => (int)$product['product_id'], 'name' => $product['name']] : null;
}

send_json($out);
