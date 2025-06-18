<?php

namespace Shopthru\Connector\Api;

use Shopthru\Connector\Api\Data\CancelOrderRequestInterface;
use Shopthru\Connector\Api\Data\ConfirmOrderRequestInterface;
use Shopthru\Connector\Api\Data\OrderImportInterface;
use Shopthru\Connector\Api\Data\OrderImportResponseInterface;

interface ImportOrderManagementInterface
{
    /**
     * Import orders from Shopthru
     *
     * @param \Shopthru\Connector\Api\Data\OrderImportInterface[] $orders
     * @return \Shopthru\Connector\Api\Data\OrderImportResponseInterface[]
     */
    public function importMultipleOrders(array $orders);

    /**
     * Import a single order from Shopthru
     *
     * @param \Shopthru\Connector\Api\Data\OrderImportInterface $order
     * @return \Shopthru\Connector\Api\Data\OrderImportResponseInterface
     */
    public function importOrder(OrderImportInterface $orderData): OrderImportResponseInterface;

    /**
     * @param string $shopthruOrderId
     * @param \Shopthru\Connector\Api\Data\ConfirmOrderRequestInterface $confirmOrderData
     * @return \Shopthru\Connector\Api\Data\OrderImportResponseInterface
     */
    public function completeOrder(string $shopthruOrderId, ConfirmOrderRequestInterface $confirmOrderData);

    /**
     * @param string $shopthruOrderId
     * @param CancelOrderRequestInterface $cancelOrderData
     * @return \Shopthru\Connector\Api\Data\OrderImportResponseInterface
     */
    public function cancelOrder($shopthruOrderId, CancelOrderRequestInterface $cancelOrderData);
}
