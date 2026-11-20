<?php
/*
 * Copyright (c) 2026 WebWorks 101 OÜ (MageBooster). All rights reserved.
 * Commercial licence, see LICENSE.txt. https://magebooster.com
 * Licensed per serial number for one production installation (one store domain); staging and
 * development copies of that store are allowed. Serial numbers and downloads are tied to the customer.
 * You may modify this code for your own licensed store; keep this notice.
 * Not allowed: copying to other stores, redistributing, reselling, sublicensing, publishing it publicly,
 * removing licence checks or notices, sharing serial numbers. Unauthorised use: the serial number is
 * revoked without refund, updates and support stop, damages and legal action may follow.
 * Estonian law applies; court: Harju County Court (Harju Maakohus).
 */
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
        private ConfigInterface $config,
        private EncryptorInterface $encryptor
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
