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

namespace MageBooster\Rubik\Block;

use Magento\Payment\Block\ConfigurableInfo;

/**
 * Order page / emails: what was charged at Rubikpara and the provider's transaction number.
 */
class Info extends ConfigurableInfo
{
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $info = $this->getInfo();
        $data = [];
        if ($info->getAdditionalInformation('charge_amount') !== null) {
            $data[(string)__('Charged amount')] = sprintf('%s %s',
                number_format((float)$info->getAdditionalInformation('charge_amount'), 2, '.', ''),
                $info->getAdditionalInformation('charge_currency'));
        }
        if ($info->getAdditionalInformation('charge_rate')) {
            $data[(string)__('Exchange rate used')] = sprintf('1 %s = %s TRY',
                $info->getOrder() ? $info->getOrder()->getBaseCurrencyCode() : '',
                rtrim(rtrim(number_format((float)$info->getAdditionalInformation('charge_rate'), 6, '.', ''), '0'), '.'));
        }
        if ($info->getAdditionalInformation('provider_txn') && !$this->getIsSecureMode()) {
            $data[(string)__('Rubikpara transaction')] = (string)$info->getAdditionalInformation('provider_txn');
        }
        return $transport->addData($data);
    }

    protected function getLabel($field)
    {
        return __($field);
    }
}
