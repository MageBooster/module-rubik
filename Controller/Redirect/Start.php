<?php
declare(strict_types=1);

namespace MageBooster\Rubik\Controller\Redirect;

use MageBooster\Rubik\Model\ChargeAmount;
use MageBooster\Rubik\Model\DebugLog;
use MageBooster\Rubik\Model\Gateway;
use MageBooster\Rubik\Model\OrderProcessor;
use MageBooster\Rubik\Model\Settings;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Right after "Place order": works out the amount to charge, opens the payment at Rubikpara and sends the customer there.
 */
class Start implements HttpGetActionInterface
{
    public function __construct(
        private CheckoutSession $checkoutSession,
        private RedirectFactory $redirectFactory,
        private ManagerInterface $messageManager,
        private ChargeAmount $chargeAmount,
        private Gateway $gateway,
        private OrderRepositoryInterface $orderRepository,
        private OrderProcessor $processor,
        private DebugLog $log
    ) {
    }

    public function execute()
    {
        $redirect = $this->redirectFactory->create();
        $order = $this->checkoutSession->getLastRealOrder();
        if (!$order->getId() || $order->getPayment()->getMethod() !== Settings::CODE
            || $order->getState() !== Order::STATE_PENDING_PAYMENT) {
            return $redirect->setPath('checkout/cart');
        }
        try {
            $charge = $this->chargeAmount->forOrder($order);
            $this->chargeAmount->remember($order, $charge);
            $url = $this->gateway->start($order, $charge);
            $this->orderRepository->save($order);
            return $redirect->setUrl($url);
        } catch (\Throwable $e) {
            $this->log->error('Start failed: ' . $e->getMessage(), ['order' => $order->getIncrementId()]);
            $this->processor->markFailed($order, $e->getMessage());
            $this->checkoutSession->restoreQuote();
            $this->messageManager->addErrorMessage($e instanceof LocalizedException
                ? $e->getMessage() : __('The payment could not be started. Please try again.'));
            return $redirect->setPath('checkout/cart');
        }
    }
}
