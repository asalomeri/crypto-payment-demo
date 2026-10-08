<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

/**
 * IPN webhook / نقطة استقبال إشعارات NOWPayments
 * Verifies HMAC-SHA512 over the JSON body with keys sorted recursively.
 * التحقق من التوقيع: HMAC-SHA512 على JSON مرتب المفاتيح.
 */
function sortRecursive(array &$a): void
{
    ksort($a);
    foreach ($a as &$v) {
        if (is_array($v)) {
            sortRecursive($v);
        }
    }
}

$secret = env('NOWPAYMENTS_IPN_SECRET');
$raw = (string) file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '';
$data = json_decode($raw, true);

if ($secret === '' || $sig === '' || !is_array($data)) {
    http_response_code(400);
    exit('Bad request');
}

sortRecursive($data);
$expected = hash_hmac('sha512', json_encode($data, JSON_UNESCAPED_SLASHES), $secret);

// Constant-time comparison / مقارنة آمنة ضد timing attacks
if (!hash_equals($expected, strtolower($sig))) {
    http_response_code(401);
    exit('Invalid signature');
}

$id = (string) ($data['payment_id'] ?? '');
if ($id === '') {
    http_response_code(400);
    exit('Missing payment_id');
}

// Reject unknown orders / رفض الطلبات غير المعروفة
if (!is_known_order((string) ($data['order_id'] ?? ''), $id)) {
    http_response_code(404);
    exit('Unknown order');
}

$file = __DIR__ . '/storage/payments.json';
$fh = fopen($file, 'c+');
if ($fh === false || !flock($fh, LOCK_EX)) {
    http_response_code(500);
    exit('Storage error');
}
$payments = json_decode((string) stream_get_contents($fh), true);
$payments = is_array($payments) ? $payments : [];
$payments[$id] = [
    'status' => (string) ($data['payment_status'] ?? ''),
    'order_id' => (string) ($data['order_id'] ?? ''),
    'updated_at' => date('c'),
];
ftruncate($fh, 0);
rewind($fh);
fwrite($fh, json_encode($payments, JSON_PRETTY_PRINT));
flock($fh, LOCK_UN);
fclose($fh);

http_response_code(200);
echo 'OK';
