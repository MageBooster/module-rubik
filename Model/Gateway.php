<?php
declare(strict_types=1);

namespace MageBooster\Rubik\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;

/**
 * Rubikpara API: a single-use, fixed-amount 3D Secure payment link per order (createLink),
 * its result (link-status) and refunds (return). Card details are entered on Rubikpara's page only.
 */
class Gateway
{
    private const TRY_CODE = 949;

    public function __construct(
        private readonly Settings $settings,
        private readonly Http $http,
        private readonly ReturnUrl $returnUrl,
        private readonly DebugLog $log
    ) {
    }

    public function start(Order $order, array $charge): string
    {
        $payment = $order->getPayment();
        if ($payment->getAdditionalInformation('pay_url')) {
            return (string)$payment->getAdditionalInformation('pay_url');
        }
        if ($charge['currency'] !== 'TRY') {
            throw new LocalizedException(__('Rubikpara only accepts Turkish lira.'));
        }
        $incrementId = (string)$order->getIncrementId();
        $data = $this->call($order, '/v1/Payments/createLink', [
            'linkType' => 'SingleUse',
            'expiryDate' => (new \DateTimeImmutable('+1 hour'))->format('Y-m-d\TH:i:s'),
            'orderId' => $incrementId,
            'linkAmountType' => 'FixedAmount',
            'amount' => $charge['amount'],
            'currency' => self::TRY_CODE,
            'installments' => 1,
            'is3dRequired' => true,
            'productName' => 'Order #' . $incrementId,
            'returnUrl' => $this->returnUrl->build('mbrubik/payment/callback', $incrementId),
        ]);
        if (empty($data['isSuccess']) || empty($data['linkUrl'])) {
            $this->log->error('createLink refused', ['order' => $incrementId, 'answer' => $data]);
            throw new LocalizedException(__('Rubikpara could not open the payment page. Please try again.'));
        }
        $payment->setAdditionalInformation('link_id', (string)($data['id'] ?? ''));
        $payment->setAdditionalInformation('pay_url', (string)$data['linkUrl']);
        return (string)$data['linkUrl'];
    }

    /**
     * @return array{state: string, txn?: string, reason?: string}
     */
    public function status(Order $order): array
    {
        $linkId = (string)$order->getPayment()->getAdditionalInformation('link_id');
        if ($linkId === '') {
            return ['state' => 'pending'];
        }
        $data = $this->call($order, '/v1/Payments/link-status', ['id' => $linkId]);
        $paymentStatus = strtolower((string)($data['linkPaymentStatus'] ?? ''));
        $linkStatus = strtolower((string)($data['linkStatus'] ?? ''));
        if ($paymentStatus === 'success') {
            return ['state' => 'paid', 'txn' => $linkId];
        }
        if (in_array($paymentStatus, ['failed', 'expired'], true) || in_array($linkStatus, ['expired', 'cancelled'], true)) {
            return ['state' => 'failed', 'reason' => trim($paymentStatus . ' ' . $linkStatus)];
        }
        return ['state' => 'pending'];
    }

    public function refund(InfoInterface $payment, float $amount): string
    {
        $order = $payment->getOrder();
        $data = $this->call($order, '/v1/Payments/return', [
            'orderId' => (string)$order->getIncrementId(),
            'languageCode' => 'TR',
            'amount' => $amount,
        ]);
        if (empty($data['isSuccess'])) {
            throw new LocalizedException(__('Rubikpara refused the refund: %1', (string)($data['message'] ?? $data['errorMessage'] ?? '?')));
        }
        return (string)($data['id'] ?? $data['transactionId'] ?? '');
    }

    private function call(Order $order, string $path, array $body): array
    {
        $storeId = (int)$order->getStoreId();
        $conversationId = $order->getIncrementId() . '-' . bin2hex(random_bytes(4));
        $data = $this->http->postJson($this->settings->baseUrl($storeId) . $path, $body,
            $this->headers($storeId, $conversationId, (string)($order->getRemoteIp() ?: '127.0.0.1')));
        return isset($data['data']) && is_array($data['data']) ? $data['data'] + $data : $data;
    }

    /**
     * securityData = base64(HMAC-SHA256(PublicKey + Nonce)), Signature = base64(HMAC-SHA256(SecretKey + ConversationId + Nonce + securityData)),
     * both keyed with the base64-decoded secret key.
     */
    private function headers(int $storeId, string $conversationId, string $clientIp): array
    {
        $public = $this->settings->value('public_key', $storeId);
        $secret = $this->settings->secret('secret_key', $storeId);
        $merchant = $this->settings->value('merchant_number', $storeId);
        if ($public === '' || $secret === '' || $merchant === '') {
            throw new LocalizedException(__('Rubikpara credentials are not set.'));
        }
        $key = base64_decode($secret, true);
        if ($key === false) {
            throw new LocalizedException(__('The Rubikpara secret key is not valid.'));
        }
        $nonce = (string)(int)round(microtime(true) * 1000);
        $securityData = base64_encode(hash_hmac('sha256', $public . $nonce, $key, true));
        return [
            'PublicKey' => $public,
            'Nonce' => $nonce,
            'Signature' => base64_encode(hash_hmac('sha256', $secret . $conversationId . $nonce . $securityData, $key, true)),
            'ConversationId' => $conversationId,
            'MerchantNumber' => $merchant,
            'ClientIpAddress' => $clientIp,
        ];
    }
}
