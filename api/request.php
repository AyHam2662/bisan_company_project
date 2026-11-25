<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (!is_post_request()) {
    send_json_error('طريقة الطلب غير مسموحة.', 405);
}
require_valid_csrf();

if (post_text('website') !== '') {
    send_json(['ok' => true, 'message' => 'تم الاستلام.']);
}

$now = time();
if (!empty($_SESSION['last_form_at']) && $now - $_SESSION['last_form_at'] < 15) {
    send_json_error('يرجى الانتظار قليلاً قبل إرسال طلب آخر.', 429);
}

$form = post_text('form', 20);
if (!in_array($form, ['maintenance', 'contact'], true)) {
    send_json_error('نوع النموذج غير معروف.');
}

$name    = post_text('name', 150);
$phone   = post_text('phone', 30);
$email   = post_text('email', 150);
$message = post_text('message', 3000);
$errors  = [];

if (mb_strlen($name) < 2) {
    $errors['name'] = 'يرجى إدخال الاسم.';
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'البريد الإلكتروني غير صالح.';
}

if ($form === 'maintenance') {
    $serviceId = (int)($_POST['service_id'] ?? 0);
    $productId = (int)($_POST['product_id'] ?? 0);
    $address   = post_text('address', 255);
    $date      = post_text('preferred_date', 10);

    if ($phone === '' || !is_valid_phone($phone)) {
        $errors['phone'] = 'يرجى إدخال رقم هاتف صحيح.';
    }
    $service = $serviceId ? db_fetch_one('SELECT * FROM service WHERE service_id = ? AND is_active = 1', [$serviceId]) : null;
    if (!$service) {
        $errors['service_id'] = 'يرجى اختيار نوع الخدمة.';
    }
    if ($date !== '') {
        if (!is_valid_date($date)) {
            $errors['preferred_date'] = 'التاريخ غير صالح.';
        } elseif ($date < date('Y-m-d')) {
            $errors['preferred_date'] = 'اختر تاريخاً اليوم أو بعده.';
        }
    }
    $product = $productId ? db_fetch_one('SELECT product_id, name, model FROM product WHERE product_id = ?', [$productId]) : null;
} else {
    $subject = post_text('subject', 200);
    if ($email === '') {
        $errors['email'] = 'يرجى إدخال البريد الإلكتروني للرد عليك.';
    }
    if ($phone !== '' && !is_valid_phone($phone)) {
        $errors['phone'] = 'رقم الهاتف غير صالح.';
    }
    if (mb_strlen($subject) < 3) {
        $errors['subject'] = 'يرجى كتابة موضوع الرسالة.';
    }
    if (mb_strlen($message) < 5) {
        $errors['message'] = 'يرجى كتابة نص الرسالة.';
    }
    $address = '';
}

if ($errors) {
    send_json_error('يرجى تصحيح الحقول المحددة.', 422, $errors);
}

$pdo = db_connection();
try {
    $pdo->beginTransaction();

    $normPhone = preg_replace('/[^0-9+]/', '', $phone);
    $customer = null;
    if ($normPhone !== '') {
        $customer = db_fetch_one("SELECT customer_id FROM customer WHERE REGEXP_REPLACE(phone, '[^0-9+]', '') = ? LIMIT 1", [$normPhone]);
    }
    if (!$customer && $email !== '') {
        $customer = db_fetch_one('SELECT customer_id FROM customer WHERE email = ? LIMIT 1', [$email]);
    }
    if ($customer) {
        $customerId = (int)$customer['customer_id'];
        db_query('UPDATE customer SET name = ?,
              email   = COALESCE(NULLIF(?, \'\'), email),
              address = COALESCE(NULLIF(?, \'\'), address),
              phone   = COALESCE(phone, NULLIF(?, \'\'))
           WHERE customer_id = ?', [$name, $email, $address, $phone, $customerId]);
    } else {
        db_query('INSERT INTO customer (name, type, address, email, phone) VALUES (?, \'individual\', ?, ?, ?)',
            [$name, $address ?: null, $email ?: null, $phone ?: null]);
        $customerId = (int)$pdo->lastInsertId();
    }

    if ($form === 'maintenance') {
        $type = match ($service['category']) {
            'installation', 'central' => 'installation',
            'emergency'               => 'emergency',
            default                   => 'maintenance',
        };
        $priority = $type === 'emergency' ? 'urgent' : 'normal';
        $msg = $message;
        if ($product) {
            $msg = 'المنتج المطلوب: ' . $product['name'] . ($product['model'] ? ' (' . $product['model'] . ')' : '') . ($msg !== '' ? "\n" . $msg : '');
        }
        db_query('INSERT INTO service_request (customer_id, service_id, request_type, status, priority, phone, address, preferred_date, message)
           VALUES (?, ?, ?, \'new\', ?, ?, ?, ?, ?)',
            [$customerId, $service['service_id'], $type, $priority, $phone, $address ?: null, $date ?: null, $msg ?: null]);
        $requestId = (int)$pdo->lastInsertId();
        $reply = 'تم استلام طلبك رقم ' . format_request_code($requestId) . '، وسيتواصل معك فريق التنسيق لتأكيد الموعد قريباً.';
    } else {
        db_query('INSERT INTO contact_message (customer_id, subject, message, status) VALUES (?, ?, ?, \'unread\')',
            [$customerId, $subject, $message]);
        $requestId = (int)$pdo->lastInsertId();
        $reply = 'شكراً لتواصلك معنا، تم استلام رسالتك وسنرد عليك في أقرب وقت.';
    }

    $pdo->commit();
    $_SESSION['last_form_at'] = $now;
    send_json(['ok' => true, 'message' => $reply, 'request_id' => $requestId]);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[bisan] request.php: ' . $error->getMessage());
    send_json_error('تعذّر حفظ الطلب حالياً، حاول مجدداً أو اتصل بنا هاتفياً.' . (APP_DEBUG ? ' ' . $error->getMessage() : ''), 500);
}
