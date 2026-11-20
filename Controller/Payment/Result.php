<?php
declare(strict_types=1);

namespace MageBooster\Rubik\Controller\Payment;

use MageBooster\Rubik\Model\OrderProcessor;
use MageBooster\Rubik\Model\ReturnUrl;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\Order;

/**
 * Last step in the customer's browser (a plain GET, so the session cookie is there again):
 * success page when paid, otherwise back to the cart with the items restored.
 */
class Result implements HttpGetActionInterface
{
    public function __construct(
        private RequestInterface $request,
        private RedirectFactory $redirectFactory,
        private CheckoutSession $checkoutSession,
        private ManagerInterface $messageManager,
        private OrderProcessor $processor,
        private ReturnUrl $returnUrl
    ) {
    }

    public function execute()
    {
        $incrementId = (string)$this->request->getParam('o');
        $redirect = $this->redirectFactory->create();
        $order = $this->returnUrl->isValid($incrementId, (string)$this->request->getParam('s'))
            ? $this->processor->load($incrementId) : null;
        if ($order && $order->getState() === Order::STATE_PROCESSING) {
            $this->checkoutSession->setLastOrderId($order->getId())
                ->setLastRealOrderId($order->getIncrementId())
                ->setLastQuoteId($order->getQuoteId())
                ->setLastSuccessQuoteId($order->getQuoteId());
            return $redirect->setPath('checkout/onepage/success');
        }
        if ($order && $this->processor->isOpen($order)) {
            $this->messageManager->addNoticeMessage(__('We are still waiting for the payment confirmation. You will get an email when it arrives.'));
            return $redirect->setPath('checkout/cart');
        }
        if ($order && (string)$this->checkoutSession->getLastRealOrderId() === $order->getIncrementId()) {
            $this->checkoutSession->restoreQuote();
        }
        $this->messageManager->addErrorMessage(__('The payment was not completed. Your cart is waiting for you, please try again.'));
        return $redirect->setPath('checkout/cart');
    }
}
