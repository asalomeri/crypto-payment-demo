<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\NowPaymentsClient;
use App\OrderStore;
use App\PaymentValidator;

$client = new NowPaymentsClient(
    env('NOWPAYMENTS_API_KEY'),
    env('NOWPAYMENTS_BASE_URL', 'https://api.nowpayments.io'),
    filter_var(env('NOWPAYMENTS_SANDBOX', 'true'), FILTER_VALIDATE_BOOLEAN)
);
$store = new OrderStore(__DIR__ . '/storage');

$error = null;
$payment = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Input validation / التحقق من المدخلات
    $amountRaw = $_POST['amount'] ?? '';
    $fiat = strtolower(trim((string) ($_POST['price_currency'] ?? '')));
    $crypto = strtolower(trim((string) ($_POST['pay_currency'] ?? '')));

    if (env('NOWPAYMENTS_API_KEY') === '') {
        $error = 'API key not configured / مفتاح API غير مضبوط';
    } else {
        try {
            // Whitelist from the API, falls back to a built-in list / قائمة العملات من الواجهة
            try {
                $supported = $client->getCurrencies();
            } catch (\Throwable) {
                $supported = [];
            }
            $error = PaymentValidator::validate($amountRaw, $fiat, $crypto, $supported);
            if ($error === null) {
                $amount = (float) $amountRaw;
                $orderId = bin2hex(random_bytes(8));
                $payment = $client->createPayment($amount, $fiat, $crypto, $orderId);
                // Persist the order so webhooks can be bound to it / حفظ الطلب لربط الـ Webhook
                $store->saveOrder($orderId, [
                    'price_amount' => $amount,
                    'price_currency' => $fiat,
                    'pay_currency' => $crypto,
                    'pay_address' => (string) ($payment['pay_address'] ?? ''),
                    'payment_id' => (string) ($payment['payment_id'] ?? ''),
                    'created_at' => date('c'),
                ]);
            }
        } catch (\Throwable) {
            // Never show raw API error bodies / لا نعرض رسائل الواجهة الخام
            $payment = null;
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
