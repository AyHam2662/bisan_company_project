<?php

function escape_html($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function file_url(?string $path, string $fallback = ''): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return $fallback;
    }
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }
    return url($path);
}

const COMPANY_KEYS = [
    'company_name'        => 'name',
    'company_description' => 'description',
    'logo'                => 'logo',
    'phone'               => 'phone',
    'email'               => 'email',
    'address'             => 'address',
];

function get_setting(string $key, string $default = '', bool $reset = false): string
{
    static $cache = null;
    if ($cache === null || $reset) {
        $cache = [];
        foreach (db_fetch_all('SELECT setting_key, setting_value FROM setting') as $row) {
            $cache[$row['setting_key']] = (string)$row['setting_value'];
        }
        $company = db_fetch_one('SELECT * FROM company WHERE company_id = 1') ?? [];
        foreach (COMPANY_KEYS as $settingKey => $column) {
            $cache[$settingKey] = (string)($company[$column] ?? '');
        }
    }
    return ($cache[$key] ?? '') !== '' ? $cache[$key] : $default;
}

function format_price($amount): string
{
    if ($amount === null || $amount === '') {
        return 'حسب المعاينة';
    }
    $number = (float)$amount;
    $decimals = floor($number) == $number ? 0 : 2;
    return number_format($number, $decimals) . ' ₪';
}

function service_price_text(array $service): string
{
    if ($service['base_price'] === null) {
        return $service['category'] === 'contract' ? 'باقات مخصصة' : 'حسب المعاينة';
    }
    if (!empty($service['price_unit'])) {
        return format_price($service['base_price']) . ' ' . $service['price_unit'];
    }
    return 'يبدأ من ' . format_price($service['base_price']);
}

function phone_link(string $phone): string
{
    return 'tel:' . preg_replace('/[^0-9+]/', '', $phone);
}

function product_to_array(array $product): array
{
    return [
        'id'        => (int)$product['product_id'],
        'name'      => $product['name'],
        'brand'     => $product['brand'],
        'model'     => $product['model'] ?? null,
        'category'  => $product['category'] ?? null,
        'price'     => (float)$product['price'],
        'price_text'=> format_price($product['price']),
        'available' => (bool)$product['is_available'],
        'image'     => file_url($product['image'] ?? '', url('assets/img/placeholder.svg')),
        'url'       => url('product.html?id=' . (int)$product['product_id']),
    ];
}

function service_to_array(array $service): array
{
    return [
        'id'          => (int)$service['service_id'],
        'name'        => $service['service_name'],
        'description' => $service['description'],
        'category'    => $service['category'],
        'icon'        => $service['icon'],
        'price_text'  => service_price_text($service),
        'emergency'   => $service['category'] === 'emergency',
        'url'         => url('maintenance.html?service=' . (int)$service['service_id']),
    ];
}

function format_request_code(int $requestId): string
{
    return '#MNT-' . $requestId;
}

function parse_specs(?string $specs): array
{
    $out = [];
    foreach (preg_split('/\R/u', (string)$specs) as $line) {
        if (trim($line) === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line, 2));
        $out[] = [$parts[0], $parts[1] ?? ''];
    }
    return $out;
}

function enum_labels(string $group): array
{
    static $map = [
        'request_status' => [
            'new' => ['جديد', 'info'], 'in_progress' => ['قيد التنفيذ', 'warning'],
            'completed' => ['مكتمل', 'success'], 'cancelled' => ['ملغي', 'neutral'],
        ],
        'message_status' => [
            'unread' => ['غير مقروءة', 'error'], 'read' => ['مقروءة', 'info'],
            'replied' => ['تم الرد', 'success'], 'archived' => ['مؤرشفة', 'neutral'],
        ],
        'admin_role' => [
            'super_admin' => ['مشرف عام', 'success'], 'admin' => ['مشرف', 'info'],
        ],
        'request_type' => [
            'maintenance' => ['صيانة', 'info'], 'installation' => ['تركيب', 'info'],
            'emergency' => ['طوارئ', 'error'], 'inquiry' => ['استفسار', 'neutral'],
        ],
        'priority' => [
            'low' => ['منخفضة', 'neutral'], 'normal' => ['عادية', 'info'],
            'high' => ['عالية', 'warning'], 'urgent' => ['عاجلة', 'error'],
        ],
        'customer_type' => [
            'individual' => ['فرد', 'neutral'], 'company' => ['شركة', 'info'], 'institution' => ['مؤسسة', 'success'],
        ],
        'availability' => [
            'available' => ['متاح', 'success'], 'busy' => ['مشغول', 'warning'], 'off' => ['إجازة', 'neutral'],
        ],
        'service_category' => [
            'installation' => ['تركيب', 'info'], 'maintenance' => ['صيانة', 'info'], 'gas' => ['غاز وتسريب', 'info'],
            'central' => ['تكييف مركزي', 'info'], 'emergency' => ['طوارئ', 'error'], 'contract' => ['عقود سنوية', 'success'],
        ],
        'project_type' => [
            'residential' => ['سكني', 'neutral'], 'commercial' => ['تجاري', 'info'], 'medical' => ['طبي', 'success'],
            'industrial' => ['صناعي', 'warning'], 'governmental' => ['حكومي', 'info'],
        ],
        'project_status' => [
            'planning' => ['تخطيط', 'info'], 'in_progress' => ['قيد التنفيذ', 'warning'],
            'completed' => ['مكتمل', 'success'], 'on_hold' => ['متوقف', 'neutral'],
        ],
        'unit_type' => [
            'split' => ['سبليت', 'neutral'], 'central_vrf' => ['مركزي VRF', 'neutral'], 'cassette' => ['كاسيت', 'neutral'],
            'floor_standing' => ['أرضي عمودي', 'neutral'], 'window' => ['شباك', 'neutral'], 'ducted' => ['دكت مخفي', 'neutral'],
        ],
        'invoice_status' => [
            'unpaid' => ['غير مدفوعة', 'error'], 'partial' => ['مدفوعة جزئياً', 'warning'],
            'paid' => ['مدفوعة', 'success'], 'cancelled' => ['ملغاة', 'neutral'],
        ],
        'yes_no' => ['1' => ['نعم', 'success'], '0' => ['لا', 'neutral']],
        'stock' => ['1' => ['متوفر', 'success'], '0' => ['غير متوفر', 'error']],
    ];
    return $map[$group] ?? [];
}

function enum_label_text(string $group, $value): string
{
    return enum_labels($group)[(string)$value][0] ?? (string)$value;
}

function is_post_request(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function send_json(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function send_json_error(string $message, int $code = 400, array $errors = []): void
{
    send_json(['ok' => false, 'message' => $message, 'errors' => (object)$errors], $code);
}

function post_text(string $key, int $max = 1000): string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }
    $value = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '');
    return mb_substr($value, 0, $max);
}

function is_valid_phone(string $phone): bool
{
    return (bool)preg_match('/^[0-9+\-\s()]{7,20}$/', $phone);
}

function is_valid_date(string $date): bool
{
    $parsedDate = DateTime::createFromFormat('Y-m-d', $date);
    return $parsedDate && $parsedDate->format('Y-m-d') === $date;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function is_csrf_valid(): bool
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? '');
    return is_string($sent) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $sent);
}

function require_valid_csrf(): void
{
    if (!is_csrf_valid()) {
        send_json_error('انتهت صلاحية الجلسة، حدّث الصفحة وحاول مجدداً.', 419);
    }
}

function save_uploaded_file(string $field, string $folder, array $allowedExtensions): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('فشل رفع الملف (رمز ' . (int)$file['error'] . ').');
    }
    if ($file['size'] > MAX_UPLOAD_MB * 1024 * 1024) {
        throw new RuntimeException('حجم الملف أكبر من ' . MAX_UPLOAD_MB . ' ميغابايت.');
    }
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('نوع الملف غير مسموح. الأنواع المسموحة: ' . implode(', ', $allowedExtensions));
    }
    $imageExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (in_array($extension, $imageExtensions, true) && @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('الملف ليس صورة صالحة.');
    }
    $folder = preg_replace('/[^a-z0-9_-]/i', '', $folder);
    $folderPath = UPLOAD_DIR . '/' . $folder;
    if (!is_dir($folderPath) && !mkdir($folderPath, 0775, true) && !is_dir($folderPath)) {
        throw new RuntimeException('تعذّر إنشاء مجلد الرفع.');
    }
    $name = date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    if (!move_uploaded_file($file['tmp_name'], $folderPath . '/' . $name)) {
        throw new RuntimeException('تعذّر حفظ الملف على الخادم.');
    }
    return 'uploads/' . $folder . '/' . $name;
}

function delete_uploaded_file(?string $path): void
{
    $path = (string)$path;
    if ($path === '' || !str_starts_with($path, 'uploads/') || str_contains($path, '..')) {
        return;
    }
    $full = ROOT_DIR . '/' . $path;
    if (is_file($full)) {
        @unlink($full);
    }
}
