<!-- mb-top -->
# MageBooster Rubikpara

Rubikpara 3D Secure payment links for Magento 2, by MageBooster

![Magento 2.4.4–2.4.9](https://img.shields.io/badge/Magento-2.4.4%E2%80%932.4.9-orange) ![PHP 8.0+](https://img.shields.io/badge/PHP-8.0%2B-777bb4) ![Luma | Hyvä](https://img.shields.io/badge/themes-Luma%20%7C%20Hyv%C3%A4-0a7) ![Licence: Commercial](https://img.shields.io/badge/licence-Commercial-red)

```bash
composer require magebooster/module-rubik
bin/magento setup:upgrade
```

Paid packages are served from the MageBooster Composer repository (`https://repo.magebooster.com`); your serial number is the password. See the guide for the full steps.

- Guide: [English](docs/HOWTO.en.md) · [Türkçe](docs/HOWTO.tr.md)
- Changes: [CHANGELOG.md](CHANGELOG.md)
- Buy a licence: https://magebooster.com
- Support: info@magebooster.com

---

<!-- /mb-top -->
# MageBooster_Rubik – Rubikpara for Magento 2

Rubikpara payment method for Magento Open Source / Adobe Commerce **2.4.9** and Mage-OS (PHP 8.0+ – 8.5).
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
  including **MageBooster Checkout**.
- It doesn't depend on the native multi-step step navigator.
- **Hyvä:** the Knockout renderer is for Luma-based checkouts (also a Hyvä theme with the Luma checkout fallback).
  On a Hyvä theme with **MageBooster Checkout**, install `magebooster/module-rubik-hyva` for the method panel; the
  redirect itself works without it, because the module sets `payment/<code>/order_place_redirect_url` to
  `mbrubik/redirect/start` and MageBooster Checkout sends the customer there after placing. Hyvä Checkout
  (Magewire) is not supported yet.
- Payment always happens on the Rubikpara page (hosted payment link), so there is no in-checkout card form.

## Not tested yet

- Not run against Rubikpara yet (no test credentials).
- The signature (HMAC-SHA256 of PublicKey+Nonce, then SecretKey+ConversationId+Nonce+securityData, keyed with the
  Base64-decoded secret) follows the docs but has not been confirmed by a real call.
- The full `link-status` answer and the `return` (refund) answer are not documented; the fields used are
  `linkPaymentStatus`, `linkStatus` and `isSuccess`.
- The live API address is not public.

## Licence & copying

Commercial licence, see `LICENSE.txt`. Copyright (c) 2026 WebWorks 101 OÜ (MageBooster). All rights reserved.

- One serial number = one production installation (one store domain). Staging and development copies of that same store are allowed.
- Each serial number and each download is tied to the customer who bought it. Luma and Hyvä editions are separate products with separate serial numbers.
- You may modify the code for your own licensed store. Keep the copyright and licence notices.
- Not allowed: copying to other stores, redistributing, reselling, sublicensing, publishing in public repositories, marketplaces or forums, removing licence checks or these notices, sharing serial numbers.
- In case of unauthorised copying or use, the serial number is revoked without refund, updates and support stop, and WebWorks 101 OÜ may claim damages and take legal action. Estonian law applies; the court is Harju County Court.

Rubikpara is a trademark of its owner; this module is not made or endorsed by them.

---

# MageBooster_Rubik – Magento 2 için Rubikpara

Magento Open Source / Adobe Commerce **2.4.9** ve Mage-OS için Rubikpara ödeme yöntemi (PHP 8.0+ – 8.5).

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
  adımlı checkout'larda (**MageBooster Checkout** dahil) çalışır. Çok adımlı step navigator'a bağımlı değildir.
- **Hyvä:** Knockout renderer Luma tabanlı checkout'lar içindir (Luma checkout'a düşen Hyvä teması dahil). Hyvä
  temasında **MageBooster Checkout** ile yöntem paneli için `magebooster/module-rubik-hyva` kurun; yönlendirme onsuz da
  çalışır, çünkü modül `payment/<code>/order_place_redirect_url` değerini `mbrubik/redirect/start` yapar ve
  MageBooster Checkout sipariş sonrası müşteriyi oraya gönderir. Hyvä Checkout (Magewire) henüz desteklenmiyor.
- Ödeme her zaman Rubikpara sayfasında (barındırılan ödeme linki) yapılır; ödeme adımında kart formu yoktur.

## Henüz test edilmeyenler

- Henüz Rubikpara'ya karşı çalıştırılmadı (test bilgileri yok).
- İmza (PublicKey+Nonce, ardından SecretKey+ConversationId+Nonce+securityData için Base64 çözülmüş secret ile HMAC-SHA256)
  dokümana göre yazıldı, gerçek çağrıyla doğrulanmadı.
- `link-status` ve `return` (iade) yanıtlarının tamamı dokümanda yok; kullanılan alanlar `linkPaymentStatus`, `linkStatus`, `isSuccess`.
- Canlı API adresi herkese açık değil.

## Lisans ve kopyalama

Ticari lisans, bkz. `LICENSE.txt`. Telif hakkı (c) 2026 WebWorks 101 OÜ (MageBooster). Tüm hakları saklıdır.

- Bir seri numarası = bir canlı kurulum (bir mağaza alan adı). Aynı mağazanın test (staging) ve geliştirme kopyaları serbesttir.
- Her seri numarası ve her indirme, satın alan müşteriye bağlıdır. Luma ve Hyvä sürümleri ayrı ürünlerdir, seri numaraları da ayrıdır.
- Kodu kendi lisanslı mağazanız için değiştirebilirsiniz. Telif ve lisans notlarını silmeyin.
- Yasak olanlar: başka mağazalara kopyalamak, dağıtmak, yeniden satmak, alt lisans vermek, herkese açık depolarda, pazar yerlerinde veya forumlarda yayınlamak, lisans kontrolünü ya da bu notları kaldırmak, seri numarasını paylaşmak.
- İzinsiz kopyalama veya kullanımda seri numarası iade yapılmadan iptal edilir, güncelleme ve destek durur; WebWorks 101 OÜ tazminat isteyebilir ve yasal yola gidebilir. Estonya hukuku geçerlidir; yetkili mahkeme Harju İlçe Mahkemesi'dir (Harju Maakohus).

Rubikpara kendi sahibinin markasıdır; bu modül onlar tarafından yapılmamış veya onaylanmamıştır.
