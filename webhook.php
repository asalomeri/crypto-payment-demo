<?php

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\OrderStore;
use App\WebhookHandler;

/**
 * IPN webhook / نقطة استقبال إشعارات NOWPayments
 * Verifies HMAC-SHA512 over the JSON body with keys sorted recursively,
 * then binds the event to a stored order. See src/WebhookHandler.php.
 */
$handler = new WebhookHandler(env('NOWPAYMENTS_IPN_SECRET'), new OrderStore(__DIR__ . '/storage'));
[$code, $body] = $handler->handle(
    (string) file_get_contents('php://input'),
    (string) ($_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '')
);

http_response_code($code);
echo $body;
