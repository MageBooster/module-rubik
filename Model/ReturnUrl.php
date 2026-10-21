<?php
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
        private readonly UrlInterface $url,
        private readonly EncryptorInterface $encryptor
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
