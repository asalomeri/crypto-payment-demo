<?php

declare(strict_types=1);

namespace App;

/**
 * Verifies and records NOWPayments IPN events (testable, no globals).
 * التحقق من إشعارات NOWPayments وتسجيلها.
 */
class WebhookHandler
{
    /** Higher rank = later state / رتبة أعلى = حالة أحدث */
    private const RANKS = [
        'waiting' => 0,
        'confirming' => 1,
        'confirmed' => 2,
        'sending' => 3,
        'partially_paid' => 3,
        'failed' => 4,
        'expired' => 4,
        'finished' => 5,
        'refunded' => 6,
    ];

    private const TERMINAL = ['finished', 'failed', 'expired', 'refunded'];

    public function __construct(private string $secret, private OrderStore $store)
    {
    }

    /**
     * @return array{0:int,1:string} HTTP status code and body
     */
    public function handle(string $rawBody, string $signature): array
    {
        $data = json_decode($rawBody, true);
        if ($this->secret === '' || $signature === '' || !is_array($data)) {
            return [400, 'Bad request'];
        }

        self::sortRecursive($data);
        $expected = hash_hmac('sha512', (string) json_encode($data, JSON_UNESCAPED_SLASHES), $this->secret);

        // Constant-time comparison / مقارنة آمنة ضد timing attacks
        if (!hash_equals($expected, strtolower($signature))) {
            return [401, 'Invalid signature'];
        }

        $paymentId = (string) ($data['payment_id'] ?? '');
        $orderId = (string) ($data['order_id'] ?? '');
        if ($paymentId === '' || $orderId === '') {
            return [400, 'Missing payment_id or order_id'];
        }

        try {
            // Bind the event to a known order / ربط الحدث بطلب معروف
            $order = $this->store->getOrder($orderId);
            if ($order === null) {
                return [404, 'Unknown order'];
            }
            if (!$this->matchesOrder($data, $order)) {
                return [422, 'Order mismatch'];
            }

            $status = strtolower((string) ($data['payment_status'] ?? ''));
            if (!isset(self::RANKS[$status])) {
                return [422, 'Unknown status'];
            }

            $this->store->updatePayment($paymentId, function (?array $current) use ($status, $orderId): ?array {
                if ($current !== null && !$this->isLater($status, (string) ($current['status'] ?? ''))) {
                    return null; // idempotent: ignore replays / stale events
                }
                return [
                    'status' => $status,
                    'order_id' => $orderId,
                    'updated_at' => date('c'),
                ];
            });
        } catch (\RuntimeException) {
            return [500, 'Storage error'];
        }

        return [200, 'OK'];
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $order
     */
    private function matchesOrder(array $data, array $order): bool
    {
        if (!is_numeric($data['price_amount'] ?? null) || !is_numeric($order['price_amount'] ?? null)
            || abs((float) $data['price_amount'] - (float) $order['price_amount']) > 0.00000001) {
            return false;
        }
        foreach (['price_currency', 'pay_currency'] as $key) {
            if (strtolower((string) ($data[$key] ?? '')) !== strtolower((string) ($order[$key] ?? ''))) {
                return false;
            }
        }
        foreach (['pay_address', 'payment_id'] as $key) {
            if (($order[$key] ?? '') !== '' && (string) ($data[$key] ?? '') !== (string) $order[$key]) {
                return false;
            }
        }
        return true;
    }

    private function isLater(string $incoming, string $stored): bool
    {
        if (!isset(self::RANKS[$stored])) {
            return true;
        }
        if (in_array($stored, self::TERMINAL, true)) {
            return self::RANKS[$incoming] > self::RANKS[$stored];
        }
        return self::RANKS[$incoming] >= self::RANKS[$stored];
    }

    /** @param array<mixed> $a */
    private static function sortRecursive(array &$a): void
    {
        ksort($a);
        foreach ($a as &$v) {
            if (is_array($v)) {
                self::sortRecursive($v);
            }
        }
    }
}
