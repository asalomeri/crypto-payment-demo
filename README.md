# Crypto Payment Demo

مشروع تجريبي لاستقبال دفعات بالعملات الرقمية باستخدام **NOWPayments API** عبر **PHP**.  
A demo project for accepting crypto payments using **NOWPayments API** with **PHP**.

---

## ✨ Features

- إنشاء فاتورة دفع (Payment Invoice)
- متابعة حالة الدفع (Pending / Confirmed / Finished)
- دعم مدفوعات العملات الرقمية بسهولة
- قابل للتوسعة لربطه بمتجر أو SaaS

---

## 🧱 Tech Stack

- **Backend:** PHP
- **Payments:** NOWPayments API
- **Format:** JSON over HTTPS

---

## 📂 Project Structure

```text
crypto-payment-demo/
├─ docs/
│  ├─ nft-metadata-linking.md
│  └─ nft-metadata-linking.ar.md
├─ src/               # (اختياري) ملفات المنطق البرمجي
├─ public/            # (اختياري) نقطة دخول الويب
└─ README.md
```

> ملاحظة: قد يختلف الهيكل الفعلي حسب ملفاتك الحالية.

---

## ⚙️ Setup

1. انسخ المشروع:
```bash
git clone https://github.com/asalomeri/crypto-payment-demo.git
cd crypto-payment-demo
```

2. أنشئ ملف إعدادات (مثال: `.env`) وضع مفاتيح NOWPayments:
```env
NOWPAYMENTS_API_KEY=your_api_key_here
NOWPAYMENTS_IPN_SECRET=your_ipn_secret_here
APP_URL=http://localhost
```

3. شغّل مشروع PHP بالطريقة المناسبة لبيئتك (Apache/Nginx أو built-in server).

---

## 🔌 NOWPayments Flow (Typical)

1. إنشاء Payment/Invoice عبر API
2. إعادة رابط الدفع للمستخدم
3. استقبال Webhook/IPN لتحديث حالة الطلب
4. تأكيد الطلب داخل النظام بعد نجاح الدفع

---

## 🧪 Example Metadata (NFT + GitHub)

لو تبغى ربط NFT بالمشروع، استخدم `external_url` داخل metadata:

```json
{
  "name": "Crypto Payment Demo NFT",
  "description": "Official NFT for the crypto-payment-demo project by asalomeri.",
  "image": "https://your-domain.com/assets/crypto-payment-demo-nft.png",
  "external_url": "https://github.com/asalomeri/crypto-payment-demo",
  "attributes": [
    { "trait_type": "project", "value": "crypto-payment-demo" },
    { "trait_type": "creator", "value": "asalomeri" },
    { "trait_type": "network", "value": "Solana" }
  ]
}
```

---

## 📚 Documentation

- English guide: [docs/nft-metadata-linking.md](docs/nft-metadata-linking.md)
- الدليل العربي: [docs/nft-metadata-linking.ar.md](docs/nft-metadata-linking.ar.md)

---

## 🔒 Security Notes

- لا ترفع مفاتيح API إلى GitHub.
- تحقق من توقيع/سرية Webhooks.
- استخدم HTTPS في بيئة الإنتاج.
- سجّل أحداث الدفع للمراجعة والتدقيق.

---

## 🗺️ Roadmap

- [ ] إضافة صفحة Checkout بسيطة
- [ ] دعم عملات/شبكات أكثر
- [ ] إضافة لوحة متابعة للمدفوعات
- [ ] اختبارات تكامل مع NOWPayments Sandbox

---

## 🤝 Contributing

المساهمات مرحب بها.  
1) Fork  
2) Branch جديد  
3) Commit واضح  
4) Pull Request

---

## 📄 License

يمكنك إضافة رخصة مناسبة مثل MIT في ملف `LICENSE`.

---

## 👤 Author

**asalomeri**  
GitHub: [https://github.com/asalomeri](https://github.com/asalomeri)
