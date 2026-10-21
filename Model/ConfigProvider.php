<?php
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
        private readonly Settings $settings,
        private readonly ChargeAmount $chargeAmount,
        private readonly CheckoutSession $checkoutSession
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
