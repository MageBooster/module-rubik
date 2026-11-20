<?php
declare(strict_types=1);

namespace MageBooster\Rubik\Model;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;

/**
 * Turns a confirmed or failed payment into the order state. Safe to call twice (browser return + server notification).
 */
class OrderProcessor
{
    public function __construct(
        private OrderFactory $orderFactory,
        private OrderRepositoryInterface $orderRepository,
        private OrderSender $orderSender,
        private DebugLog $log
    ) {
    }

    public function load(string $incrementId): ?Order
    {
        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
        if (!$order->getId() || $order->getPayment()->getMethod() !== Settings::CODE) {
            return null;
        }
        return $order;
    }

    public function isOpen(Order $order): bool
    {
        return $order->getState() === Order::STATE_PENDING_PAYMENT || $order->getState() === Order::STATE_NEW;
    }

    public function markPaid(Order $order, string $transactionId, array $extra = []): void
    {
        if (!$this->isOpen($order)) {
            return;
        }
        $payment = $order->getPayment();
        foreach ($extra as $key => $value) {
            $payment->setAdditionalInformation($key, $value);
        }
        $payment->setAdditionalInformation('provider_txn', $transactionId);
        $payment->setTransactionId($transactionId);
        $payment->setIsTransactionClosed(false);
        $payment->registerCaptureNotification((float)$order->getBaseGrandTotal(), true);
        $order->setState(Order::STATE_PROCESSING)
            ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));
        $order->addCommentToStatusHistory((string)__('Payment confirmed by Rubikpara (%1).', $transactionId));
        $order->setCanSendNewEmailFlag(true);
        $this->orderRepository->save($order);
        try {
            $this->orderSender->send($order);
        } catch (\Throwable $e) {
            $this->log->error('Order email failed: ' . $e->getMessage(), ['order' => $order->getIncrementId()]);
        }
        $this->log->debug('Order paid', ['order' => $order->getIncrementId(), 'txn' => $transactionId]);
    }

    /**
     * @param array{state: string, txn?: string, reason?: string, extra?: array} $status from Gateway::status()
     */
    public function apply(Order $order, array $status): void
    {
        if ($status['state'] === 'paid') {
            $this->markPaid($order, (string)($status['txn'] ?? ''), $status['extra'] ?? []);
        } elseif ($status['state'] === 'failed') {
            $this->markFailed($order, (string)($status['reason'] ?? ''));
        }
    }

    public function markFailed(Order $order, string $reason): void
    {
        if (!$this->isOpen($order)) {
            return;
        }
        $order->cancel();
        $order->addCommentToStatusHistory((string)__('Rubikpara payment failed: %1', $reason));
        $this->orderRepository->save($order);
        $this->log->debug('Order payment failed', ['order' => $order->getIncrementId(), 'reason' => $reason]);
    }
}
