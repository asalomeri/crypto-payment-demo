<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load .env if present / تحميل ملف البيئة
if (is_file(__DIR__ . '/../.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
}

function env(string $key, string $default = ''): string
{
    return (string) ($_ENV[$key] ?? getenv($key) ?: $default);
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/**
 * Record an order created by index.php / تسجيل طلب تم إنشاؤه من index.php
 */
function record_order(string $orderId, string $paymentId): void
{
    $fh = fopen(__DIR__ . '/../storage/orders.json', 'c+');
    if ($fh === false || !flock($fh, LOCK_EX)) {
        throw new \RuntimeException('Storage error');
    }
    $orders = json_decode((string) stream_get_contents($fh), true);
    $orders = is_array($orders) ? $orders : [];
    $orders[$orderId] = ['payment_id' => $paymentId, 'created_at' => date('c')];
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($orders, JSON_PRETTY_PRINT));
    flock($fh, LOCK_UN);
    fclose($fh);
}

/**
 * Check that order_id/payment_id pair was created by this app / التحقق من أن الطلب معروف
 */
function is_known_order(string $orderId, string $paymentId): bool
{
    $file = __DIR__ . '/../storage/orders.json';
    if ($orderId === '' || !is_file($file)) {
        return false;
    }
    $fh = fopen($file, 'r');
    if ($fh === false || !flock($fh, LOCK_SH)) {
        return false;
    }
    $orders = json_decode((string) stream_get_contents($fh), true);
    flock($fh, LOCK_UN);
    fclose($fh);
    return is_array($orders)
        && isset($orders[$orderId]['payment_id'])
        && hash_equals((string) $orders[$orderId]['payment_id'], $paymentId);
}
