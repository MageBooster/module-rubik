<?php
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

    public function __construct(private readonly LoggerInterface $logger, private readonly Settings $settings)
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
