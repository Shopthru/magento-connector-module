<?php

namespace Shopthru\Connector\Helper;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Shopthru\Connector\Api\Data\ImportLogInterface;
use Magento\Framework\DB\TransactionFactory;
use Shopthru\Connector\Model\EventType;

class OrderProcesses extends AbstractHelper
{
    public function __construct(
        Context $context,
        private readonly TransactionFactory $transactionFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly StockRegistryInterface $stockRegistry,
    ) {
        parent::__construct($context);
    }

    /**
     * @param OrderInterface $order
     * @param string $comment
     * @param bool $isVisibleOnFront
     * @return OrderInterface
     */
    public function addCommentToOrder(OrderInterface $order, string $comment, bool $isVisibleOnFront = false): OrderInterface
    {
        $order->addCommentToStatusHistory(
            comment:$comment,
            isVisibleOnFront:$isVisibleOnFront
        );
        $this->orderRepository->save($order);

        return $order;
    }

    /**
     * @param OrderInterface $order
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function decrementStock(OrderInterface $order): array
    {
        $stockUpdates = [];

        foreach ($order->getAllItems() as $item) {
            $productId = $item->getProductId();
            $qty = $item->getQtyOrdered();

            $stockItem = $this->stockRegistry->getStockItem($productId);
            $oldQty = $stockItem->getQty();
            $stockItem->setQty($oldQty - $qty);

            if ($stockItem->getQty() <= 0) {
                $stockItem->setIsInStock(false);
            }

            $this->stockRegistry->updateStockItemBySku($item->getSku(), $stockItem);

            $stockUpdates[] = [
                'sku' => $item->getSku(),
                'previous_qty' => $oldQty,
                'new_qty' => $stockItem->getQty(),
                'delta' => -$qty
            ];
        }
        return $stockUpdates;
    }

    /**
     * @param OrderInterface $order
     * @return \Magento\Sales\Model\Order\Invoice
     */
    public function createInvoice(OrderInterface $order): \Magento\Sales\Model\Order\Invoice
    {
        $invoice = $order->prepareInvoice();
        $invoice->register();
        $invoice->pay();

        // Explicitly mark order as paid
        $order->setTotalPaid($order->getGrandTotal());
        $order->setBaseTotalPaid($order->getBaseGrandTotal());

        // Mark order as processing
        $order->setState(Order::STATE_PROCESSING);
        $order->setStatus($this->moduleConfig->getOrderStatus() ?: Order::STATE_PROCESSING);
        $order->setIsInProcess(true);

        $dbTransaction = $this->transactionFactory->create();
        $dbTransaction->addObject(
            $invoice
        )->addObject(
            $order
        );

        $dbTransaction->save();

        return $invoice;
    }
}
