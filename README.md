# Crypto Payment Demo

> ⚠️ **This is an educational project, NOT production-ready without an additional security review.**
> ⚠️ **هذا مشروع تعليمي وليس جاهزًا للإنتاج بدون مراجعة أمنية إضافية.**

## English

A simple PHP demo showing how to accept crypto payments (BTC, ETH, USDT, ...) with the [NOWPayments API](https://documenter.getpostman.com/view/7907941/S1a32n38).

### Get API keys
Register at [nowpayments.io](https://nowpayments.io) (or the sandbox at [account-sandbox.nowpayments.io](https://account-sandbox.nowpayments.io)), create an API key and an IPN secret key in Settings.

### Install & run
```bash
composer install
cp .env.example .env   # fill in your keys
php -S localhost:8000
```
Open http://localhost:8000.

### Test the webhook locally
Expose your server with [ngrok](https://ngrok.com): `ngrok http 8000`, then set the IPN callback URL in NOWPayments to `https://<id>.ngrok.io/webhook.php`. Payment states are saved to `storage/payments.json`. Signatures are verified with HMAC-SHA512 and `hash_equals`.

### Order binding (replay / forged-event protection)
When `index.php` creates a payment it stores an order in `storage/orders.json` (keyed by `order_id`: `price_amount`, `price_currency`, `pay_currency`, `pay_address`, `payment_id`, `created_at`). After verifying the HMAC-SHA512 signature, `webhook.php` rejects events whose `order_id` is unknown (404) or whose amount, currency, address or `payment_id` don't match the stored order (422). Updates are idempotent: once a payment is in a terminal state (`finished`, `failed`, `expired`, `refunded`) replays and older states are ignored (only a later state, e.g. `finished` → `refunded`, is recorded). Input is validated against a currency whitelist (the API's `getCurrencies()` with a built-in fallback) and a max amount of 10,000.

### Security notes
`.env`, `storage/`, `src/` and `tests/` must not be web-accessible. Apache: the bundled `.htaccess` files deny them. nginx: `location ~ ^/(storage|src|tests|vendor)/ { deny all; } location ~ /\.(env|ht) { deny all; }`, or better, serve only a public docroot. `storage/payments.json` and `storage/orders.json` are git-ignored. The IPN secret is never echoed or logged, and API error bodies are never shown to users.

### Run the tests
```bash
composer install
vendor/bin/phpunit
```
Tests cover webhook signatures (valid/invalid/missing), unknown order, amount mismatch, idempotent updates, input validation, and `NowPaymentsClient` using a Guzzle `MockHandler` (no network).

## العربية

مشروع PHP بسيط يوضح كيفية استقبال دفعات بالعملات الرقمية (Bitcoin, Ethereum, USDT ...) عبر NOWPayments API، للأغراض التعليمية والتجريبية فقط.

### الحصول على مفاتيح API
سجّل في [nowpayments.io](https://nowpayments.io) (أو النسخة التجريبية sandbox) ثم أنشئ مفتاح API ومفتاح IPN Secret من الإعدادات.

### التثبيت والتشغيل
```bash
composer install
cp .env.example .env   # ضع مفاتيحك
php -S localhost:8000
```

### اختبار الـ Webhook محليًا
استخدم [ngrok](https://ngrok.com) عبر `ngrok http 8000` ثم ضع الرابط `https://<id>.ngrok.io/webhook.php` كـ IPN callback. تُحفظ الحالات في `storage/payments.json` ويتم التحقق من التوقيع بـ HMAC-SHA512 و`hash_equals`.

### ربط الـ Webhook بالطلبات (حماية من التكرار والأحداث المزوّرة)
عند إنشاء دفعة في `index.php` يُحفظ الطلب في `storage/orders.json` (بمفتاح `order_id` مع المبلغ والعملات وعنوان الدفع و`payment_id` ووقت الإنشاء). بعد التحقق من توقيع HMAC-SHA512 يرفض `webhook.php` الأحداث ذات `order_id` غير معروف (404) أو التي لا يطابق مبلغها/عملتها/عنوانها الطلب المحفوظ (422). التسجيل متكرر الأمان (idempotent): إذا كانت الحالة نهائية (`finished`, `failed`, `expired`, `refunded`) تُتجاهل الأحداث المكررة والأقدم. كما تُتحقق المدخلات من قائمة عملات وحد أقصى للمبلغ (10,000).

### ملاحظات أمنية
يجب ألا تكون `.env` و`storage/` و`src/` و`tests/` متاحة عبر الويب (ملفات `.htaccess` المرفقة تمنعها في Apache، وفي nginx استخدم `deny all`). الملفان `storage/payments.json` و`storage/orders.json` في `.gitignore`. لا يُعرض سر الـ IPN ولا رسائل الخطأ الخام من الـ API.

### تشغيل الاختبارات
```bash
composer install
vendor/bin/phpunit
```

لا تستخدم مفاتيح حقيقية في الكود ولا تقم بدفعات حقيقية في هذا الديمو.
