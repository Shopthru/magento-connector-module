<?php
/**
 * Status.php
 */
namespace Shopthru\Connector\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Shopthru\Connector\Api\Data\ImportLogInterface;
use Shopthru\Connector\Enum\ImportStatus;

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
                'value' => ImportStatus::PENDING,
                'label' => __('Pending')
            ],
            [
                'value' => ImportStatus::SUCCESS,
                'label' => __('Success')
            ],
            [
                'value' => ImportStatus::FAILED,
                'label' => __('Failed')
            ]
        ];
    }
}
