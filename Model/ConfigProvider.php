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

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Checkout data for the payment renderer: description and, when the order will be charged in TRY,
 * the rate so the TRY amount is shown below the method.
 */
class ConfigProvider implements ConfigProviderInterface
{
    public function __construct(
        private Settings $settings,
        private ChargeAmount $chargeAmount,
        private CheckoutSession $checkoutSession
    ) {
    }

    public function getConfig()
    {
        if (!$this->settings->isActive()) {
            return [];
        }
        $quote = $this->checkoutSession->getQuote();
        $convert = $this->chargeAmount->needsConversion((string)$quote->getQuoteCurrencyCode());
        return ['payment' => [Settings::CODE => [
            'description' => $this->settings->getDescription(),
            'testMode' => $this->settings->isTestMode(),
            'convertToTry' => $convert,
            'tryRate' => $convert ? $this->chargeAmount->rateToTry((string)$quote->getBaseCurrencyCode()) : null,
            'baseCurrency' => (string)$quote->getBaseCurrencyCode(),
        ]]];
    }
}
