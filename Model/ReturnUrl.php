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
use Magento\Framework\UrlInterface;

/**
 * Signed return/result URLs (order number + HMAC with the store's crypt key), so they can't be guessed.
 */
class ReturnUrl
{
    public function __construct(
        private UrlInterface $url,
        private EncryptorInterface $encryptor
    ) {
    }

    public function build(string $route, string $incrementId): string
    {
        return $this->url->getUrl($route, ['_secure' => true, '_query' => ['o' => $incrementId, 's' => $this->sign($incrementId)]]);
    }

    public function isValid(string $incrementId, string $signature): bool
    {
        return $incrementId !== '' && hash_equals($this->sign($incrementId), $signature);
    }

    private function sign(string $incrementId): string
    {
        return substr($this->encryptor->hash('magebooster_rubik|' . $incrementId), 0, 32);
    }
}
