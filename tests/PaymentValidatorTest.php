<?php

declare(strict_types=1);

namespace App\Tests;

use App\PaymentValidator;
use PHPUnit\Framework\TestCase;

final class PaymentValidatorTest extends TestCase
{
    public function testValid(): void
    {
        $this->assertNull(PaymentValidator::validate('10.5', 'usd', 'btc'));
        $this->assertNull(PaymentValidator::validate('10', 'USD', 'doge', ['doge']));
    }

    public function testInvalidAmounts(): void
    {
        foreach (['0', '-1', 'abc', '', '10000.01', 'INF'] as $a) {
            $this->assertNotNull(PaymentValidator::validate($a, 'usd', 'btc'), $a);
        }
        $this->assertNull(PaymentValidator::validate('10000', 'usd', 'btc'));
    }

    public function testCurrencyWhitelists(): void
    {
        $this->assertNotNull(PaymentValidator::validate('1', 'xyz', 'btc'));
        $this->assertNotNull(PaymentValidator::validate('1', 'usd', 'doge'));
        $this->assertNotNull(PaymentValidator::validate('1', 'usd', 'btc', ['eth']));
    }
}
