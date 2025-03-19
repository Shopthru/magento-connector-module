<?php

namespace Shopthru\Connector\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Shopthru\Connector\Api\Data\ImportLogInterface;

class Status extends Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['status'])) {
                    switch ($item['status']) {
                        case ImportLogInterface::STATUS_PENDING:
                            $class = 'grid-severity-minor';
                            break;
                        case ImportLogInterface::STATUS_SUCCESS:
                            $class = 'grid-severity-notice';
                            break;
                        case ImportLogInterface::STATUS_FAILED:
                            $class = 'grid-severity-critical';
                            break;
                        default:
                            $class = 'grid-severity-minor';
                    }

                    $item['status_html'] = '<span class="' . $class . '"><span>' . $item['status'] . '</span></span>';
                }
            }
        }

        return $dataSource;
    }
}
