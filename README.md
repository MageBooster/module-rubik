# MageBooster_Rubik – Rubikpara for Magento 2

Free (MIT) Rubikpara payment method for Magento Open Source / Adobe Commerce **2.4.9** and Mage-OS (PHP 8.0+ – 8.5).
Made by [MageBooster](https://magebooster.com). Türkçe açıklama aşağıda.

## Install

```bash
composer require magebooster/module-rubik
bin/magento module:enable MageBooster_Rubik
bin/magento setup:upgrade
bin/magento setup:di:compile && bin/magento setup:static-content:deploy   # production mode
```

Manual: copy this folder to `app/code/MageBooster/Rubik` and run the same commands.

## Settings

Stores › Configuration › Sales › Payment Methods › **Rubikpara · MageBooster**:
enabled, title, checkout text, test mode, credentials (secrets are stored encrypted), test and live API address,
currencies charged directly, allowed countries, sort order, debug log (`var/log/magebooster_mbrubik.log`, card data and secrets are masked).

Credentials: Merchant number, public key, secret key (Base64). Test API: `https://testpfapi.rubikpara.com`; ask Rubikpara for the live API address.

## How it works

After the order is placed, a single-use, fixed-amount 3D Secure payment link is created (`/v1/Payments/createLink`)
and the customer pays on Rubikpara's page. On the way back (`/mbrubik/payment/callback`) nothing in the request is trusted:
the module asks Rubikpara for the link status (`/v1/Payments/link-status`). A cron job does the same every 10 minutes for
customers who closed the browser before coming back.

- The order is created as **pending payment** and the order email waits until the payment is confirmed.
- Paid: an invoice is created automatically and the order moves to **processing**.
- Failed or cancelled: the order is cancelled and the customer's cart is restored.
- Unpaid orders are cancelled by Magento's own *Pending Payment Order Lifetime* (Sales › Sales › Orders Cron Settings).
- Refunds: Credit memo → "Refund" calls `/v1/Payments/return` (full or partial).
- Return URLs carry the order number signed with your store's crypt key, so they can't be guessed.

**PCI DSS:** card details are entered on Rubikpara's page only; your store never sees them.

## Currencies and TRY

Charged directly: TRY only (other currencies are converted, see below). When the order currency is something else, the base grand total is converted to TRY
with the rate saved in Stores › Currency Rates. The TRY amount and rate are shown under the payment method at checkout,
stored on the payment, and shown on the order page, so the customer is never charged a different amount than shown.
For daily Central Bank of Türkiye (TCMB) rates use **MageBooster Currency Rates** (paid); without it, any Magento rate source works.

## Checkout compatibility

- Uses the standard Magento payment renderer (`checkout_index_index` layout + a `Magento_Checkout/js/view/payment/default`
  method renderer), so it works in the native checkout and in one-step checkouts that render standard payment methods,
  including the free and Pro **MageBooster Checkout**.
- It doesn't depend on the native multi-step step navigator.
- **Hyvä:** the Knockout renderer is for Luma-based checkouts. Hyvä Checkout needs a small Magewire/Alpine payment
  component; the server side (redirect, callback, refund) is the same. A Hyvä compatibility module is planned.

## Not tested yet

- Not run against Rubikpara yet (no test credentials).
- The signature (HMAC-SHA256 of PublicKey+Nonce, then SecretKey+ConversationId+Nonce+securityData, keyed with the
  Base64-decoded secret) follows the docs but has not been confirmed by a real call.
- The full `link-status` answer and the `return` (refund) answer are not documented; the fields used are
  `linkPaymentStatus`, `linkStatus` and `isSuccess`.
- The live API address is not public.

## License

MIT. Rubikpara is a trademark of its owner; this module is not made or endorsed by them.

---

# MageBooster_Rubik – Magento 2 için Rubikpara

Magento Open Source / Adobe Commerce **2.4.9** ve Mage-OS için ücretsiz (MIT) Rubikpara ödeme yöntemi (PHP 8.0+ – 8.5).

## Kurulum

```bash
composer require magebooster/module-rubik
bin/magento module:enable MageBooster_Rubik
bin/magento setup:upgrade
bin/magento setup:di:compile && bin/magento setup:static-content:deploy tr_TR en_US
```

## Ayarlar

Mağazalar › Yapılandırma › Satış › Ödeme Yöntemleri › **Rubikpara · MageBooster**: etkin, başlık, ödeme adımı metni, test modu,
API bilgileri (gizli alanlar şifreli saklanır), test ve canlı API adresi, doğrudan tahsil edilen para birimleri, ülkeler,
sıralama, hata ayıklama kaydı (`var/log/magebooster_mbrubik.log`; kart bilgisi ve gizli anahtarlar maskelenir).

Gerekli bilgiler: Üye işyeri numarası, public key, secret key (Base64). Test API: `https://testpfapi.rubikpara.com`; canlı API adresini Rubikpara'dan isteyin.

## Nasıl çalışır

Sipariş verildikten sonra tek kullanımlık, sabit tutarlı 3D Secure ödeme linki oluşturulur (`/v1/Payments/createLink`)
ve müşteri Rubikpara sayfasında öder. Dönüşte (`/mbrubik/payment/callback`) istekteki hiçbir veriye güvenilmez:
modül link durumunu Rubikpara'ya sorar (`/v1/Payments/link-status`). Tarayıcıyı dönmeden kapatan müşteriler için bir cron
görevi her 10 dakikada aynı kontrolü yapar.

- Sipariş **pending payment** durumunda açılır, sipariş e-postası ödeme onaylanınca gider.
- Ödeme başarılıysa fatura otomatik kesilir, sipariş **processing** olur.
- Başarısız veya iptalse sipariş iptal edilir, müşterinin sepeti geri yüklenir.
- Ödenmeyen siparişleri Magento'nun *Pending Payment Order Lifetime* ayarı iptal eder.
- İade: Credit memo → "Refund" `/v1/Payments/return` çağırır (tam veya kısmi).
- Dönüş adresleri mağazanın şifreleme anahtarıyla imzalı sipariş numarası taşır, tahmin edilemez.

**PCI DSS:** kart bilgileri yalnızca Rubikpara sayfasında girilir; mağazanız bu bilgileri hiç görmez.

## Para birimi ve TL

Doğrudan tahsil: TRY only (other currencies are converted, see below). Sipariş başka para birimindeyse ana para birimindeki toplam, Mağazalar › Döviz Kurları'nda
kayıtlı kurla TL'ye çevrilir. TL tutarı ve kur ödeme adımında yöntemin altında gösterilir, ödemeye kaydedilir ve sipariş
sayfasında görünür; müşteriden gösterilenden farklı tutar çekilmez. Günlük TCMB kurları için **MageBooster Currency Rates**
(ücretli) kullanılabilir; yoksa Magento'nun herhangi bir kur kaynağı çalışır.

## Checkout uyumluluğu

- Standart Magento ödeme renderer'ı kullanılır; Magento'nun kendi checkout'unda ve standart ödeme yöntemlerini gösteren tek
  adımlı checkout'larda (ücretsiz ve Pro **MageBooster Checkout** dahil) çalışır. Çok adımlı step navigator'a bağımlı değildir.
- **Hyvä:** Knockout renderer Luma tabanlı checkout içindir. Hyvä Checkout için küçük bir Magewire/Alpine ödeme bileşeni
  gerekir; sunucu tarafı (yönlendirme, dönüş, iade) aynıdır.

## Henüz test edilmeyenler

- Henüz Rubikpara'ya karşı çalıştırılmadı (test bilgileri yok).
- İmza (PublicKey+Nonce, ardından SecretKey+ConversationId+Nonce+securityData için Base64 çözülmüş secret ile HMAC-SHA256)
  dokümana göre yazıldı, gerçek çağrıyla doğrulanmadı.
- `link-status` ve `return` (iade) yanıtlarının tamamı dokümanda yok; kullanılan alanlar `linkPaymentStatus`, `linkStatus`, `isSuccess`.
- Canlı API adresi herkese açık değil.

## Lisans

MIT. Rubikpara kendi sahibinin markasıdır; bu modül onlar tarafından yapılmamış veya onaylanmamıştır.
