<?php
declare(strict_types=1);

namespace MageBooster\Rubik\Model;

use Magento\Directory\Model\CurrencyFactory;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Works out what is charged at Rubikpara: the order total in the order currency when Rubikpara accepts it,
 * otherwise the base total converted to TRY with the store's saved rate (Stores > Currency Rates;
 * with MageBooster Currency Rates these are TCMB rates). The result is stored on the payment and shown
 * in checkout and on the order, so the customer is never charged a different amount than shown.
 */
class ChargeAmount
{
    public function __construct(
        private CurrencyFactory $currencyFactory,
        private Settings $settings
    ) {
    }

    /**
     * @return array{amount: float, currency: string, rate: ?float, converted: bool}
     */
    public function forOrder(OrderInterface $order): array
    {
        $orderCurrency = (string)$order->getOrderCurrencyCode();
        if (in_array($orderCurrency, $this->settings->getSupportedCurrencies(), true)) {
            return ['amount' => round((float)$order->getGrandTotal(), 2), 'currency' => $orderCurrency, 'rate' => null, 'converted' => false];
        }
        $rate = $this->rateToTry((string)$order->getBaseCurrencyCode());
        if ($rate === null) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('There is no TRY exchange rate for %1. Please contact the store.', $order->getBaseCurrencyCode())
            );
        }
        return [
            'amount' => round((float)$order->getBaseGrandTotal() * $rate, 2),
            'currency' => 'TRY',
            'rate' => $rate,
            'converted' => true,
        ];
    }

    /**
     * Rate "1 base currency = X TRY", or null when the store has none.
     */
    public function rateToTry(string $baseCurrency): ?float
    {
        if ($baseCurrency === 'TRY') {
            return 1.0;
        }
        $rate = $this->currencyFactory->create()->load($baseCurrency)->getRate('TRY');
        return $rate ? (float)$rate : null;
    }

    public function needsConversion(string $quoteCurrency): bool
    {
        return !in_array($quoteCurrency, $this->settings->getSupportedCurrencies(), true);
    }

    /**
     * Store the charge on the payment (shown on the order page and used for refunds).
     */
    public function remember(OrderInterface $order, array $charge): void
    {
        $payment = $order->getPayment();
        $payment->setAdditionalInformation('charge_amount', $charge['amount']);
        $payment->setAdditionalInformation('charge_currency', $charge['currency']);
        $payment->setAdditionalInformation('charge_rate', $charge['rate']);
    }
}
