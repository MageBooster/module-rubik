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

namespace MageBooster\Rubik\Observer;

use MageBooster\License\Api\LicenseInterface;
use MageBooster\Rubik\Model\ChargeAmount;
use MageBooster\Rubik\Model\Settings;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Hides the method in checkout when the cart currency must be converted to TRY but the store has no rate,
 * so the customer is not sent to a payment step that can only fail. Also hides it when the MageBooster
 * license is not active (no valid serial and the 30-day grace period is over). Other payment methods,
 * callbacks, refunds and existing orders are not affected.
 */
class HideWithoutRate implements ObserverInterface
{
    public const LICENSE_CODE = 'rubik';

    /**
     * @param ChargeAmount $chargeAmount
     * @param LicenseInterface $license
     */
    public function __construct(
        private ChargeAmount $chargeAmount,
        private LicenseInterface $license
    ) {
    }

    /**
     * Turn availability off for this method when no TRY rate exists for the base currency
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();
        $method = $event->getData('method_instance');
        $result = $event->getData('result');
        $quote = $event->getData('quote');
        if (!$method || !$result || !$quote || $method->getCode() !== Settings::CODE || !$result->getData('is_available')) {
            return;
        }
        if (!$this->isLicensed()) {
            $result->setData('is_available', false);
            return;
        }
        $currency = (string)$quote->getQuoteCurrencyCode();
        if ($currency === '' || !$this->chargeAmount->needsConversion($currency)) {
            return;
        }
        if ($this->chargeAmount->rateToTry((string)$quote->getBaseCurrencyCode()) === null) {
            $result->setData('is_available', false);
        }
    }

    private function isLicensed(): bool
    {
        try {
            return $this->license->isActive(self::LICENSE_CODE);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
