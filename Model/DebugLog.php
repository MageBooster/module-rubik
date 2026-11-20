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

use Psr\Log\LoggerInterface;

/**
 * Debug log (var/log/magebooster_mbrubik.log), only when "Debug" is on. Card data and secrets are masked before writing.
 */
class DebugLog
{
    private const MASK = ['cardnumber', 'cvcnumber', 'cvc', 'cvv', 'expmonth', 'expyear', 'month', 'year', 'password',
        'checkkey', 'secretkey', 'clientsecret', 'signature', 'token', 'accesstoken', 'access_token', 'authorization', 'card'];

    public function __construct(private LoggerInterface $logger, private Settings $settings)
    {
    }

    public function debug(string $message, array $context = []): void
    {
        if ($this->settings->isDebug()) {
            $this->logger->debug($message, $this->mask($context));
        }
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $this->mask($context));
    }

    public function mask(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = in_array(strtolower((string)$key), self::MASK, true) ? '***' : $this->mask($value);
            } elseif (in_array(strtolower((string)$key), self::MASK, true)) {
                $data[$key] = '***';
            }
        }
        return $data;
    }
}
