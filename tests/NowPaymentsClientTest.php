<?php

declare(strict_types=1);

namespace App\Tests;

use App\NowPaymentsClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class NowPaymentsClientTest extends TestCase
{
    /** @var array<int,array<string,mixed>> */
    private array $history = [];

    /** @param array<int,mixed> $responses */
    private function client(array $responses): NowPaymentsClient
    {
        $this->history = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));
        $http = new Client(['handler' => $stack, 'base_uri' => 'https://api.test/']);
        return new NowPaymentsClient('key123', 'https://api.test', false, $http);
    }

    public function testCreatePaymentSuccess(): void
    {
        $c = $this->client([new Response(201, [], json_encode(['payment_id' => '1', 'pay_address' => 'a']))]);
        $r = $c->createPayment(5.0, 'USD', 'BTC', 'ord1');

        $this->assertSame('a', $r['pay_address']);
        $req = $this->history[0]['request'];
        $this->assertSame('POST', $req->getMethod());
        $this->assertSame('/v1/payment', $req->getUri()->getPath());
        $this->assertSame('key123', $req->getHeaderLine('x-api-key'));
        $body = json_decode((string) $req->getBody(), true);
        $this->assertEquals(['price_amount' => 5.0, 'price_currency' => 'usd', 'pay_currency' => 'btc', 'order_id' => 'ord1'], $body);
    }

    public function testCreatePaymentApiError(): void
    {
        $c = $this->client([new Response(400, [], '{"message":"bad"}')]);
        $this->expectException(ClientException::class);
        $c->createPayment(5.0, 'usd', 'btc', 'ord1');
    }

    public function testGetPaymentStatusSuccess(): void
    {
        $c = $this->client([new Response(200, [], json_encode(['payment_status' => 'finished']))]);
        $this->assertSame('finished', $c->getPaymentStatus('42')['payment_status']);
        $this->assertSame('/v1/payment/42', $this->history[0]['request']->getUri()->getPath());
    }

    public function testGetPaymentStatusError(): void
    {
        $c = $this->client([new Response(404, [], '{}')]);
        $this->expectException(ClientException::class);
        $c->getPaymentStatus('42');
    }

    public function testGetCurrenciesSuccess(): void
    {
        $c = $this->client([new Response(200, [], json_encode(['currencies' => ['btc', 'eth']]))]);
        $this->assertSame(['btc', 'eth'], $c->getCurrencies());
    }

    public function testGetCurrenciesInvalidResponse(): void
    {
        $c = $this->client([new Response(200, [], 'not json')]);
        $this->expectException(\RuntimeException::class);
        $c->getCurrencies();
    }

    public function testGetCurrenciesServerError(): void
    {
        $c = $this->client([new Response(500, [], 'oops')]);
        $this->expectException(\GuzzleHttp\Exception\ServerException::class);
        $c->getCurrencies();
    }
}
