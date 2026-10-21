<?php
declare(strict_types=1);

namespace MageBooster\Rubik\Model;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Payment\Gateway\ConfigInterface;

/**
 * Admin settings of the Rubikpara method (Stores > Configuration > Sales > Payment Methods).
 */
class Settings
{
    public const CODE = 'magebooster_rubik';

    public function __construct(
        private readonly ConfigInterface $config,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    public function value(string $field, ?int $storeId = null): string
    {
        return trim((string)$this->config->getValue($field, $storeId));
    }

    /** Encrypted (obscure) fields. */
    public function secret(string $field, ?int $storeId = null): string
    {
        $value = $this->value($field, $storeId);
        return $value === '' ? '' : trim($this->encryptor->decrypt($value));
    }

    public function isActive(?int $storeId = null): bool
    {
        return $this->value('active', $storeId) === '1';
    }

    public function isTestMode(?int $storeId = null): bool
    {
        return $this->value('test_mode', $storeId) === '1';
    }

    public function isDebug(?int $storeId = null): bool
    {
        return $this->value('debug', $storeId) === '1';
    }

    public function getDescription(?int $storeId = null): string
    {
        return $this->value('description', $storeId);
    }

    /** Currencies Rubikpara can charge directly; anything else is converted to TRY. */
    public function getSupportedCurrencies(?int $storeId = null): array
    {
        $list = array_map('trim', explode(',', strtoupper($this->value('charge_currencies', $storeId))));
        return array_values(array_filter($list)) ?: ['TRY'];
    }

    public function baseUrl(?int $storeId = null): string
    {
        $url = rtrim($this->value($this->isTestMode($storeId) ? 'test_url' : 'live_url', $storeId), '/');
        if ($url === '') {
            throw new \Magento\Framework\Exception\LocalizedException(__('Rubikpara API address is not set.'));
        }
        return $url;
    }
}
