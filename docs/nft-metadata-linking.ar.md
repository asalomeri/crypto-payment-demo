# ربط NFT بمشروع GitHub (دليل عملي)

هذا الدليل يشرح طريقة ربط NFT على Solana بملفك أو مشروعك على GitHub بشكل احترافي، مع مثال Metadata جاهز.

## لماذا هذا مهم؟
ربط الـ NFT بمشروع GitHub يضيف:
- مصداقية أعلى (هوية واضحة)
- توثيق أفضل للمشروع
- سهولة التحقق من المصدر للمستخدمين والمشترين

---

## الحقول الأساسية في Metadata

| الحقل | ماذا يفعل؟ | ملاحظات |
|---|---|---|
| `name` | اسم الـ NFT | اجعله واضحًا وثابتًا |
| `description` | وصف العمل/المشروع | اذكر الهدف وهوية المشروع |
| `image` | رابط الصورة/الملف المعروض | يفضّل رابط دائم |
| `external_url` | رابط خارجي رسمي | أفضل مكان لوضع GitHub |
| `attributes` | خصائص إضافية | مثل النوع، الشبكة، الإصدار |
| `properties.files` | الملفات المرتبطة | يفضل تضمين نوع الملف `type` |

---

## مثال Metadata (JSON) جاهز

> غيّر الروابط التجريبية (`your-domain.com`) إلى روابطك الحقيقية قبل الاستخدام.

```json
{
  "name": "Minty Camera Roll",
  "symbol": "MCR",
  "description": "Original digital collectible by asalomeri. Source and project details are available on GitHub.",
  "image": "https://your-domain.com/assets/minty-camera-roll.png",
  "external_url": "https://github.com/asalomeri",
  "attributes": [
    { "trait_type": "project", "value": "crypto-payment-demo" },
    { "trait_type": "creator", "value": "asalomeri" },
    { "trait_type": "network", "value": "Solana" },
    { "trait_type": "type", "value": "NFT" }
  ],
  "properties": {
    "files": [
      {
        "uri": "https://your-domain.com/assets/minty-camera-roll.png",
        "type": "image/png"
      }
    ],
    "category": "image"
  }
}
```

---

## خطوات التنفيذ السريعة

1. جهّز الصورة النهائية وملف `metadata.json`.
2. ارفع الصورة وملف JSON على استضافة دائمة:
   - Arweave
   - IPFS (Pinned)
3. استخدم رابط JSON النهائي أثناء السك (Mint).
4. أضف في README الخاص بالمشروع:
   - رابط الـ NFT على Solscan
   - رابط metadata
   - رابط GitHub/المشروع

---

## أفضل الممارسات

- **هوية متسقة**: نفس الاسم والوصف والشعار بين NFT وGitHub.
- **استضافة دائمة**: لا تعتمد على روابط مؤقتة.
- **إشارات ثقة**:
  - نشر عنوان الـ Mint في README
  - توثيق الـ creator عندما يكون ممكنًا
  - توضيح الجهة المالكة للمشروع

---

## مهم: حدود التعديل بعد السك

إذا كان الـ NFT **مُسكّ بالفعل**:
- التعديل يتطلب صلاحية **Update Authority**.
- قد تكون أنت **Owner** لكن لا تملك **Update Authority**.
- في NFTs المسكوكة عبر خدمات وسيطة أو cNFTs، غالبًا التعديل محدود.

### الحلول البديلة عند عدم امتلاك Update Authority
- إنشاء صفحة مشروع رسمية تربط الـ NFT بـ GitHub.
- إضافة التوثيق الكامل في README.
- سكّ NFT جديد ببيانات Metadata منظمة وهوية موثقة.
