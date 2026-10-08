<?php

declare(strict_types=1);

namespace App\Tests;

use App\OrderStore;
use App\WebhookHandler;
use PHPUnit\Framework\TestCase;

final class WebhookHandlerTest extends TestCase
{
    private const SECRET = 'test-ipn-secret';

    private string $dir;
    private OrderStore $store;
    private WebhookHandler $handler;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/nowp_' . bin2hex(random_bytes(6));
        $this->store = new OrderStore($this->dir);
        $this->handler = new WebhookHandler(self::SECRET, $this->store);
        $this->store->saveOrder('ord1', [
            'price_amount' => 10.5,
            'price_currency' => 'usd',
            'pay_currency' => 'btc',
            'pay_address' => 'addr1',
            'payment_id' => '555',
        ]);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $f) {
            unlink($f);
        }
        @rmdir($this->dir);
    }

    /** @param array<string,mixed> $override */
    private function event(array $override = []): array
    {
        return array_merge([
            'payment_id' => '555',
            'payment_status' => 'waiting',
            'order_id' => 'ord1',
            'price_amount' => 10.5,
            'price_currency' => 'usd',
            'pay_currency' => 'btc',
            'pay_address' => 'addr1',
        ], $override);
    }

    private function sign(array $data): string
    {
        $sort = function (array &$a) use (&$sort): void {
            ksort($a);
            foreach ($a as &$v) {
                if (is_array($v)) {
                    $sort($v);
                }
            }
        };
        $sort($data);
        return hash_hmac('sha512', json_encode($data, JSON_UNESCAPED_SLASHES), self::SECRET);
    }

    /** @return array{0:int,1:string} */
    private function send(array $data, ?string $sig = null): array
    {
        return $this->handler->handle(json_encode($data), $sig ?? $this->sign($data));
    }

    public function testValidSignatureIsRecorded(): void
    {
        $this->assertSame([200, 'OK'], $this->send($this->event()));
        $this->assertSame('waiting', $this->store->getPayment('555')['status']);
        $this->assertSame('ord1', $this->store->getPayment('555')['order_id']);
    }

    public function testUppercaseSignatureIsAccepted(): void
    {
        $e = $this->event();
        $this->assertSame(200, $this->send($e, strtoupper($this->sign($e)))[0]);
    }

    public function testInvalidSignatureRejected(): void
    {
        $this->assertSame([401, 'Invalid signature'], $this->send($this->event(), str_repeat('a', 128)));
        $this->assertNull($this->store->getPayment('555'));
    }

    public function testTamperedBodyRejected(): void
    {
        $sig = $this->sign($this->event());
        $this->assertSame(401, $this->send($this->event(['price_amount' => 1.0]), $sig)[0]);
    }

    public function testMissingSignatureRejected(): void
    {
        $this->assertSame(400, $this->send($this->event(), '')[0]);
        $this->assertSame(400, $this->handler->handle('not json', 'abc')[0]);
    }

    public function testMissingSecretRejected(): void
    {
        $h = new WebhookHandler('', $this->store);
        $this->assertSame(400, $h->handle(json_encode($this->event()), 'abc')[0]);
    }

    public function testUnknownOrderRejected(): void
    {
        $this->assertSame(404, $this->send($this->event(['order_id' => 'nope']))[0]);
        $this->assertNull($this->store->getPayment('555'));
    }

    public function testAmountMismatchRejected(): void
    {
        $this->assertSame(422, $this->send($this->event(['price_amount' => 1.0]))[0]);
        $this->assertNull($this->store->getPayment('555'));
    }

    public function testCurrencyMismatchRejected(): void
    {
        $this->assertSame(422, $this->send($this->event(['price_currency' => 'eur']))[0]);
        $this->assertSame(422, $this->send($this->event(['pay_currency' => 'eth']))[0]);
    }

    public function testPaymentIdMismatchRejected(): void
    {
        $this->assertSame(422, $this->send($this->event(['payment_id' => '999']))[0]);
    }

    public function testUnknownStatusRejected(): void
    {
        $this->assertSame(422, $this->send($this->event(['payment_status' => 'bogus']))[0]);
    }

    public function testProgressionAndStaleEventIgnored(): void
    {
        $this->send($this->event(['payment_status' => 'confirming']));
        $this->send($this->event(['payment_status' => 'waiting']));
        $this->assertSame('confirming', $this->store->getPayment('555')['status']);
        $this->send($this->event(['payment_status' => 'finished']));
        $this->assertSame('finished', $this->store->getPayment('555')['status']);
    }

    public function testTerminalStatusIsIdempotent(): void
    {
        $this->send($this->event(['payment_status' => 'finished']));
        $first = $this->store->getPayment('555');

        foreach (['finished', 'waiting', 'failed', 'expired'] as $status) {
            $this->assertSame(200, $this->send($this->event(['payment_status' => $status]))[0]);
            $this->assertSame($first, $this->store->getPayment('555'));
        }

        $this->send($this->event(['payment_status' => 'refunded']));
        $this->assertSame('refunded', $this->store->getPayment('555')['status']);
        $this->send($this->event(['payment_status' => 'finished']));
        $this->assertSame('refunded', $this->store->getPayment('555')['status']);
    }
}
