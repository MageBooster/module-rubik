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

namespace MageBooster\Rubik\Cron;

use MageBooster\Rubik\Model\DebugLog;
use MageBooster\Rubik\Model\Gateway;
use MageBooster\Rubik\Model\OrderProcessor;
use MageBooster\Rubik\Model\Settings;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

/**
 * Every 10 minutes: asks Rubikpara about orders still waiting for payment (customer closed the browser before coming back).
 * Unpaid orders are cancelled by Magento's own "Pending Payment Order Lifetime" (Sales > Sales > Orders Cron Settings).
 */
class Reconcile
{
    public function __construct(
        private CollectionFactory $collectionFactory,
        private Gateway $gateway,
        private OrderProcessor $processor,
        private DebugLog $log
    ) {
    }

    public function execute(): void
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('main_table.state', Order::STATE_PENDING_PAYMENT)
            ->addFieldToFilter('main_table.created_at', ['gteq' => gmdate('Y-m-d H:i:s', time() - 2 * 86400)])
            ->addFieldToFilter('main_table.created_at', ['lteq' => gmdate('Y-m-d H:i:s', time() - 600)]);
        $collection->getSelect()
            ->join(['mbp' => $collection->getTable('sales_order_payment')], 'mbp.parent_id = main_table.entity_id', [])
            ->where('mbp.method = ?', Settings::CODE);
        $collection->setPageSize(50);
        foreach ($collection as $order) {
            try {
                $this->processor->apply($order, $this->gateway->status($order));
            } catch (\Throwable $e) {
                $this->log->error('Reconcile failed: ' . $e->getMessage(), ['order' => $order->getIncrementId()]);
            }
        }
    }
}
