<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\NowPaymentsClient;

$client = new NowPaymentsClient(
    env('NOWPAYMENTS_API_KEY'),
    env('NOWPAYMENTS_BASE_URL', 'https://api.nowpayments.io'),
    filter_var(env('NOWPAYMENTS_SANDBOX', 'true'), FILTER_VALIDATE_BOOLEAN)
);

$error = null;
$payment = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Input validation / التحقق من المدخلات
    $amount = filter_var($_POST['amount'] ?? '', FILTER_VALIDATE_FLOAT);
    $fiat = strtolower(trim((string) ($_POST['price_currency'] ?? '')));
    $crypto = strtolower(trim((string) ($_POST['pay_currency'] ?? '')));

    if ($amount === false || $amount <= 0 || $amount > 100000) {
        $error = 'Invalid amount / مبلغ غير صالح';
    } elseif (!preg_match('/^[a-z]{3,5}$/', $fiat)) {
        $error = 'Invalid price currency / عملة السعر غير صالحة';
    } elseif (!preg_match('/^[a-z0-9]{2,15}$/', $crypto)) {
        $error = 'Invalid crypto currency / عملة رقمية غير صالحة';
    } elseif (env('NOWPAYMENTS_API_KEY') === '') {
        $error = 'API key not configured / مفتاح API غير مضبوط';
    } else {
        try {
            $orderId = bin2hex(random_bytes(8));
            $payment = $client->createPayment($amount, $fiat, $crypto, $orderId);
            record_order($orderId, (string) ($payment['payment_id'] ?? ''));
        } catch (\Throwable $ex) {
            $error = 'API error / خطأ في الواجهة';
        }
    }
}
?>
<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <title>Crypto Payment Demo</title>
</head>
<body>
<h1>Crypto Payment Demo (educational / تعليمي)</h1>
<?php if ($error): ?><p style="color:red"><?= e($error) ?></p><?php endif; ?>
<?php if ($payment): ?>
    <h2>Payment created / تم إنشاء الدفعة</h2>
    <ul>
        <li>pay_address: <code><?= e((string) ($payment['pay_address'] ?? '')) ?></code></li>
        <li>pay_amount: <?= e((string) ($payment['pay_amount'] ?? '')) ?> <?= e((string) ($payment['pay_currency'] ?? '')) ?></li>
        <li>status: <?= e((string) ($payment['payment_status'] ?? '')) ?></li>
    </ul>
    <img alt="QR" src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&amp;data=<?= e(rawurlencode((string) ($payment['pay_address'] ?? ''))) ?>">
<?php else: ?>
    <form method="post">
        <label>Amount / المبلغ <input name="amount" type="number" step="0.01" min="0.01" required></label><br>
        <label>Price currency / عملة السعر <input name="price_currency" value="usd" required></label><br>
        <label>Pay currency / عملة الدفع <input name="pay_currency" value="btc" required></label><br>
        <button type="submit">Pay / ادفع</button>
    </form>
<?php endif; ?>
</body>
</html>
