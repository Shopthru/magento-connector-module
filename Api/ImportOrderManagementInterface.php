<?php

namespace Shopthru\Connector\Api;

interface ImportOrderManagementInterface
{
    /**
     * Import orders from Shopthru
     *
     * @param \Shopthru\Connector\Api\Data\OrderImportInterface[] $orders
     * @return \Shopthru\Connector\Api\Data\ImportLogInterface[]
     */
    public function importOrders(array $orders);
}
