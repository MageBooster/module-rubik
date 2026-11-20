<?php
declare(strict_types=1);

namespace MageBooster\Rubik\Observer;

use MageBooster\Rubik\Model\ChargeAmount;
use MageBooster\Rubik\Model\Settings;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Hides the method in checkout when the cart currency must be converted to TRY but the store has no rate,
 * so the customer is not sent to a payment step that can only fail.
 */
class HideWithoutRate implements ObserverInterface
{
    /**
     * @param ChargeAmount $chargeAmount
     */
    public function __construct(private ChargeAmount $chargeAmount)
    {
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
        $currency = (string)$quote->getQuoteCurrencyCode();
        if ($currency === '' || !$this->chargeAmount->needsConversion($currency)) {
            return;
        }
        if ($this->chargeAmount->rateToTry((string)$quote->getBaseCurrencyCode()) === null) {
            $result->setData('is_available', false);
        }
    }
}
