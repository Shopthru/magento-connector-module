<?php

namespace Shopthru\Connector\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CancelledOrderAction implements OptionSourceInterface
{
    public const UPDATE_STATUS = 'update_status';
    public const DELETE = 'delete';

    public function toOptionArray()
    {
        return [
            [
                'value' => self::UPDATE_STATUS,
                'label' => __('Update order status')
            ],
            [
                'value' => self::DELETE,
                'label' => __('Delete order')
            ]
        ];
    }
}
