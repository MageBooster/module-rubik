<?php
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
