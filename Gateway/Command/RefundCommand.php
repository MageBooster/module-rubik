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

namespace MageBooster\Rubik\Gateway\Command;

use MageBooster\Rubik\Model\Gateway;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

/**
 * Online refund from a credit memo. The credit memo is in base currency; Rubikpara is refunded in the currency it charged
 * (with the rate stored at checkout when the order was converted to TRY).
 */
class RefundCommand implements CommandInterface
{
    public function __construct(private Gateway $gateway)
    {
    }

    public function execute(array $commandSubject)
    {
        $payment = SubjectReader::readPayment($commandSubject)->getPayment();
        $baseAmount = (float)SubjectReader::readAmount($commandSubject);
        $order = $payment->getOrder();
        $rate = (float)$payment->getAdditionalInformation('charge_rate');
        $amount = round($baseAmount * ($rate ?: ((float)$order->getBaseToOrderRate() ?: 1.0)), 2);
        $reference = $this->gateway->refund($payment, $amount);
        $refunds = (array)$payment->getAdditionalInformation('refunds');
        $refunds[] = ['amount' => $amount, 'currency' => $payment->getAdditionalInformation('charge_currency'), 'ref' => $reference];
        $payment->setAdditionalInformation('refunds', $refunds);
        return null;
    }
}
