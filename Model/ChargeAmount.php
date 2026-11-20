<?php
/*
 * Copyright (c) 2026 WebWorks 101 OÜ (MageBooster). All rights reserved.
 * Commercial licence, see LICENSE.txt. https://magebooster.com
 * Licensed per serial number for one production installation (one store domain); staging and
 * development copies of that store are allowed. Serial numbers and downloads are tied to the customer.
 * You may modify this code for your own licensed store; keep this notice.
 * Not allowed: copying to other stores, redistributing, reselling, sublicensing, publishing it publicly,
 * removing licence checks or notices, sharing serial numbers. Unauthorised use: the serial number is
 * revoked without refund, updates and support stop, damages and legal action may follow.
 * Estonian law applies; court: Harju County Court (Harju Maakohus).
 */
declare(strict_types=1);

namespace MageBooster\Rubik\Model;

use Magento\Directory\Model\CurrencyFactory;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Works out what is charged at Rubikpara: the order total in the order currency when Rubikpara accepts it,
 * otherwise the base total converted to TRY with the store's saved rate (Stores > Currency Rates;
 * with MageBooster Currency Rates these are TCMB rates). The result is stored on the payment and shown
 * in checkout and on the order, so the customer is never charged a different amount than shown.
 */
class ChargeAmount
{
    public function __construct(
        private CurrencyFactory $currencyFactory,
        private Settings $settings
    ) {
    }

    /**
     * @return array{amount: float, currency: string, rate: ?float, converted: bool}
     */
    public function forOrder(OrderInterface $order): array
    {
        $orderCurrency = (string)$order->getOrderCurrencyCode();
        if (in_array($orderCurrency, $this->settings->getSupportedCurrencies(), true)) {
            return ['amount' => round((float)$order->getGrandTotal(), 2), 'currency' => $orderCurrency, 'rate' => null, 'converted' => false];
        }
        $rate = $this->rateToTry((string)$order->getBaseCurrencyCode());
        if ($rate === null) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('There is no TRY exchange rate for %1. Please contact the store.', $order->getBaseCurrencyCode())
            );
        }
        return [
            'amount' => round((float)$order->getBaseGrandTotal() * $rate, 2),
            'currency' => 'TRY',
            'rate' => $rate,
            'converted' => true,
        ];
    }

    /**
     * Rate "1 base currency = X TRY", or null when the store has none.
     */
    public function rateToTry(string $baseCurrency): ?float
    {
        if ($baseCurrency === 'TRY') {
            return 1.0;
        }
        $rate = $this->currencyFactory->create()->load($baseCurrency)->getRate('TRY');
        return $rate ? (float)$rate : null;
    }

    public function needsConversion(string $quoteCurrency): bool
    {
        return !in_array($quoteCurrency, $this->settings->getSupportedCurrencies(), true);
    }

    /**
     * Store the charge on the payment (shown on the order page and used for refunds).
     */
    public function remember(OrderInterface $order, array $charge): void
    {
        $payment = $order->getPayment();
        $payment->setAdditionalInformation('charge_amount', $charge['amount']);
        $payment->setAdditionalInformation('charge_currency', $charge['currency']);
        $payment->setAdditionalInformation('charge_rate', $charge['rate']);
    }
}
