<?php

declare(strict_types=1);

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

/**
 * Minimal NOWPayments API client (educational).
 * عميل بسيط لواجهة NOWPayments (للتعليم فقط).
 */
class NowPaymentsClient
{
    private ClientInterface $http;

    public function __construct(
        private string $apiKey,
        string $baseUrl = 'https://api.nowpayments.io',
        bool $sandbox = true,
        ?ClientInterface $http = null
    ) {
        // Sandbox uses a different host / الوضع التجريبي له عنوان مختلف
        if ($sandbox && $baseUrl === 'https://api.nowpayments.io') {
            $baseUrl = 'https://api-sandbox.nowpayments.io';
        }
        $this->http = $http ?? new Client([
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'timeout' => 15,
        ]);
    }

    /**
     * Create a payment: POST /v1/payment
     * إنشاء دفعة جديدة
     *
     * @return array<string,mixed>
     */
    public function createPayment(
        float $amount,
        string $priceCurrency,
        string $payCurrency,
        string $orderId,
        ?string $ipnCallbackUrl = null
    ): array {
        $body = [
            'price_amount' => $amount,
            'price_currency' => strtolower($priceCurrency),
            'pay_currency' => strtolower($payCurrency),
            'order_id' => $orderId,
        ];
        if ($ipnCallbackUrl !== null) {
            $body['ipn_callback_url'] = $ipnCallbackUrl;
        }
        return $this->request('POST', 'v1/payment', ['json' => $body]);
    }

    /**
     * Get payment status: GET /v1/payment/{id}
     * التحقق من حالة الدفع
     *
     * @return array<string,mixed>
     */
    public function getPaymentStatus(string $paymentId): array
    {
        return $this->request('GET', 'v1/payment/' . rawurlencode($paymentId));
    }

    /**
     * List supported currencies: GET /v1/currencies
     * قائمة العملات المدعومة
     *
     * @return string[]
     */
    public function getCurrencies(): array
    {
        $data = $this->request('GET', 'v1/currencies');
        return $data['currencies'] ?? [];
    }

    /**
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    private function request(string $method, string $uri, array $options = []): array
    {
        $options['headers'] = ['x-api-key' => $this->apiKey];
        $response = $this->http->request($method, $uri, $options);
        $data = json_decode((string) $response->getBody(), true);
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid API response');
        }
        return $data;
    }
}
