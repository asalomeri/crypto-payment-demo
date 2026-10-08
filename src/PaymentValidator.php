<?php

declare(strict_types=1);

namespace App;

/**
 * Validates user input before calling the API / التحقق من المدخلات قبل استدعاء الواجهة.
 */
class PaymentValidator
{
    public const MAX_AMOUNT = 10000.0;

    public const FIAT_WHITELIST = ['usd', 'eur', 'gbp', 'aud', 'cad', 'chf', 'jpy', 'sar', 'aed'];

    public const CRYPTO_FALLBACK = ['btc', 'eth', 'ltc', 'usdttrc20', 'usdterc20'];

    /**
     * @param string[] $supportedCrypto from NowPaymentsClient::getCurrencies(); empty = use fallback
     * @return string|null error message, or null when valid
     */
    public static function validate(mixed $amount, string $fiat, string $crypto, array $supportedCrypto = []): ?string
    {
        $amount = filter_var($amount, FILTER_VALIDATE_FLOAT);
        if ($amount === false || !is_finite($amount) || $amount <= 0 || $amount > self::MAX_AMOUNT) {
            return 'Invalid amount / مبلغ غير صالح';
        }
        if (!in_array(strtolower($fiat), self::FIAT_WHITELIST, true)) {
            return 'Invalid price currency / عملة السعر غير صالحة';
        }
        $allowed = $supportedCrypto === [] ? self::CRYPTO_FALLBACK : array_map('strtolower', $supportedCrypto);
        if (!in_array(strtolower($crypto), $allowed, true)) {
            return 'Invalid crypto currency / عملة رقمية غير صالحة';
        }
        return null;
    }
}
