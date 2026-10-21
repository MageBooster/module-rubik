<?php
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
        private readonly CollectionFactory $collectionFactory,
        private readonly Gateway $gateway,
        private readonly OrderProcessor $processor,
        private readonly DebugLog $log
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
