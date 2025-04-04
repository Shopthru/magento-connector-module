<?php

namespace Shopthru\Connector\Model\ImportProcessors\DirectOrderCreator;

use Magento\Framework\DataObject;

class DefaultData extends DataObject
{
    /**
     * Default order data
     *
     * @var array
     */
    private $defaultOrderData = [
        'base_shipping_tax_amount' => 0,
        'base_tax_amount' => 0,
        'base_to_global_rate' => 1,
        'base_to_order_rate' => 1,
        'shipping_tax_amount' => 0,
        'store_to_base_rate' => 0,
        'store_to_order_rate' => 0,
        'customer_note_notify' => 0,
        'customer_group_id' => 0,
        'base_shipping_discount_amount' => 0,
        'base_total_due' => 0,
        'shipping_discount_amount' => 0,
        'total_due' => 0,
        'weight' => 0,
        'discount_tax_compensation_amount' => 0,
        'base_discount_tax_compensation_amount' => 0,
        'shipping_discount_tax_compensation_amount' => 0,
        'base_shipping_discount_tax_compensation_amnt' => 0
    ];

    /**
     * Default item data
     *
     * @var array
     */
    private $defaultItemData = [
        'product_options' => [],
        'weight' => 0,
        'is_virtual' => 0,
        'is_qty_decimal' => 0,
        'discount_tax_compensation_amount' => 0,
        'base_discount_tax_compensation_amount' => 0,
        'gift_message_available' => 0,
        'qty_backordered' => 0
    ];

    /**
     * @return array
     */
    public function getDefaultOrderData()
    {
        return $this->defaultOrderData;
    }

    /**
     * @return array
     */
    public function getDefaultItemData()
    {
        return $this->defaultItemData;
    }
}
