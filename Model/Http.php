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

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * JSON over HTTPS to Rubikpara. Requests and answers go to the debug log with card data and secrets masked.
 */
class Http
{
    public function __construct(
        private CurlFactory $curlFactory,
        private Json $json,
        private DebugLog $log
    ) {
    }

    public function postJson(string $url, array $body, array $headers = []): array
    {
        $curl = $this->curlFactory->create();
        $curl->setTimeout(30);
        $curl->addHeader('Content-Type', 'application/json');
        $curl->addHeader('Accept', 'application/json');
        foreach ($headers as $name => $value) {
            $curl->addHeader($name, (string)$value);
        }
        $this->log->debug('Request ' . $url, ['body' => $body]);
        try {
            $curl->post($url, $this->json->serialize($body));
        } catch (\Throwable $e) {
            $this->log->error('Connection failed ' . $url . ': ' . $e->getMessage());
            throw new LocalizedException(__('Could not connect to Rubikpara. Please try again.'));
        }
        $status = $curl->getStatus();
        $raw = (string)$curl->getBody();
        try {
            $data = $raw === '' ? [] : $this->json->unserialize($raw);
        } catch (\Throwable) {
            $data = null;
        }
        $this->log->debug('Response ' . $status, ['body' => is_array($data) ? $data : substr($raw, 0, 500)]);
        if (!is_array($data)) {
            $this->log->error('Unexpected answer from ' . $url, ['status' => $status]);
            throw new LocalizedException(__('Rubikpara sent an unexpected answer. Please try again.'));
        }
        $data['_http_status'] = $status;
        return $data;
    }
}
