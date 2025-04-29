<?php

namespace Shopthru\Connector\Helper;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Group;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use RectorPrefix202411\Illuminate\Contracts\Queue\EntityNotFoundException;
use Shopthru\Connector\Api\Data\ImportLogInterface;
use Magento\Framework\DB\TransactionFactory;
use Shopthru\Connector\Model\EventType;
use Shopthru\Connector\Model\Config as ModuleConfig;

class OrderProcesses extends AbstractHelper
{
    public function __construct(
        Context $context,
        private readonly TransactionFactory $transactionFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly StockRegistryInterface $stockRegistry,
        private readonly OrderSender $orderSender,
        private readonly ModuleConfig $moduleConfig,
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {
        parent::__construct($context);
    }

    /**
     * @param OrderInterface $order
     * @return bool
     */
    public function sendOrderConfirmationEmail(OrderInterface $order): bool
    {
        return $this->orderSender->send($order);
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

    public function addMultipleCommentsToOrder(OrderInterface $order, array $comments, bool $isVisibleOnFront = false): OrderInterface
    {
        foreach ($comments as $comment) {
            $order->addCommentToStatusHistory(
                comment:$comment,
                isVisibleOnFront:$isVisibleOnFront
            );
        }
        $this->orderRepository->save($order);

        return $order;
    }

    public function linkCustomerToOrderIfExists(OrderInterface $order, string $customerEmail): OrderInterface
    {
        try {
            $customer = $this->customerRepository->get($customerEmail);
            $order->setCustomerId($customer->getId());
            $order->setCustomerIsGuest(0);
            $order->setCustomerGroupId($customer->getGroupId());

        } catch (EntityNotFoundException $e) {
            $order->setCustomerIsGuest(1);
            $order->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
        }

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
