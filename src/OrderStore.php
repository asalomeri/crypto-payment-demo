<?php

declare(strict_types=1);

namespace App;

/**
 * Tiny JSON-file storage for orders and payment states (educational, no database).
 * تخزين بسيط بملفات JSON للطلبات وحالات الدفع (بدون قاعدة بيانات).
 */
class OrderStore
{
    public function __construct(private string $dir)
    {
    }

    /**
     * Save the order created in index.php so webhooks can be bound to it.
     * حفظ الطلب لربط إشعارات الـ Webhook به.
     *
     * @param array<string,mixed> $order
     */
    public function saveOrder(string $orderId, array $order): void
    {
        $this->update('orders.json', function (array $orders) use ($orderId, $order): array {
            $orders[$orderId] = $order + ['created_at' => date('c')];
            return $orders;
        });
    }

    /** @return array<string,mixed>|null */
    public function getOrder(string $orderId): ?array
    {
        $orders = $this->update('orders.json', null);
        return isset($orders[$orderId]) && is_array($orders[$orderId]) ? $orders[$orderId] : null;
    }

    /** @return array<string,mixed>|null */
    public function getPayment(string $paymentId): ?array
    {
        $payments = $this->update('payments.json', null);
        return isset($payments[$paymentId]) && is_array($payments[$paymentId]) ? $payments[$paymentId] : null;
    }

    /**
     * Atomic read-modify-write of a payment record under an exclusive flock.
     * $fn receives the current record (or null) and returns the new record,
     * or null to leave it unchanged.
     * قراءة وتعديل وكتابة ذرية مع قفل حصري.
     *
     * @param callable(?array<string,mixed>):?array<string,mixed> $fn
     */
    public function updatePayment(string $paymentId, callable $fn): bool
    {
        $changed = false;
        $this->update('payments.json', function (array $payments) use ($paymentId, $fn, &$changed): array {
            $current = isset($payments[$paymentId]) && is_array($payments[$paymentId]) ? $payments[$paymentId] : null;
            $new = $fn($current);
            if ($new !== null) {
                $payments[$paymentId] = $new;
                $changed = true;
            }
            return $payments;
        });
        return $changed;
    }

    /**
     * @param callable(array<string,mixed>):array<string,mixed>|null $fn null = read only
     * @return array<string,mixed>
     */
    private function update(string $name, ?callable $fn): array
    {
        if (!is_dir($this->dir) && !@mkdir($this->dir, 0775, true) && !is_dir($this->dir)) {
            throw new \RuntimeException('Storage error');
        }
        $fh = fopen($this->dir . '/' . $name, 'c+');
        if ($fh === false || !flock($fh, $fn === null ? LOCK_SH : LOCK_EX)) {
            throw new \RuntimeException('Storage error');
        }
        try {
            $data = json_decode((string) stream_get_contents($fh), true);
            $data = is_array($data) ? $data : [];
            if ($fn !== null) {
                $data = $fn($data);
                ftruncate($fh, 0);
                rewind($fh);
                fwrite($fh, json_encode($data, JSON_PRETTY_PRINT));
                fflush($fh);
            }
            return $data;
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }
}
