<?php
declare(strict_types=1);

namespace MageBooster\Rubik\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order;

/**
 * Places the order as "pending payment" and holds back the order email until the payment is confirmed.
 */
class InitializeCommand implements CommandInterface
{
    public function execute(array $commandSubject)
    {
        $stateObject = SubjectReader::readStateObject($commandSubject);
        $payment = SubjectReader::readPayment($commandSubject)->getPayment();
        $stateObject->setState(Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus(Order::STATE_PENDING_PAYMENT);
        $stateObject->setIsNotified(false);
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);
        return null;
    }
}
