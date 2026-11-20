<?php
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
