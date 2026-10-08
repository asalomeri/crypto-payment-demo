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
Expose your server with [ngrok](https://ngrok.com): `ngrok http 8000`, then set the IPN callback URL in NOWPayments to `https://<id>.ngrok.io/webhook.php`. Payment states are saved to `storage/payments.json`. Orders created by `index.php` are tracked in `storage/orders.json`; webhooks for unknown `order_id`/`payment_id` are rejected with 404. Signatures are verified with HMAC-SHA512 and `hash_equals`.

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
استخدم [ngrok](https://ngrok.com) عبر `ngrok http 8000` ثم ضع الرابط `https://<id>.ngrok.io/webhook.php` كـ IPN callback. تُحفظ الحالات في `storage/payments.json` وتُتبَّع الطلبات المنشأة من `index.php` في `storage/orders.json` ويُرفض (404) أي webhook لطلب غير معروف. ويتم التحقق من التوقيع بـ HMAC-SHA512 و`hash_equals`.

لا تستخدم مفاتيح حقيقية في الكود ولا تقم بدفعات حقيقية في هذا الديمو.
