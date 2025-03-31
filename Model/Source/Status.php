<?php
/**
 * Status.php
 */
namespace Shopthru\Connector\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Shopthru\Connector\Api\Data\ImportLogInterface;

class Status implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => ImportLogInterface::STATUS_PENDING,
                'label' => __('Pending')
            ],
            [
                'value' => ImportLogInterface::STATUS_SUCCESS,
                'label' => __('Success')
            ],
            [
                'value' => ImportLogInterface::STATUS_FAILED,
                'label' => __('Failed')
            ]
        ];
    }
}
