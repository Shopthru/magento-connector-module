<?php
/**
 * Actions.php
 */
namespace Shopthru\Connector\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

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
                if (isset($item['import_id'])) {
                    $viewUrl = $this->urlBuilder->getUrl(
                        'shopthru/import/view',
                        ['id' => $item['import_id']]
                    );

                    $item[$this->getData('name')] = [
                        'view' => [
                            'href' => $viewUrl,
                            'label' => __('View')
                        ]
                    ];

                    // Add link to Magento order if exists
                    if (!empty($item['magento_order_id'])) {
                        $orderUrl = $this->urlBuilder->getUrl(
                            'sales/order/view',
                            ['order_id' => $item['magento_order_id']]
                        );

                        $item[$this->getData('name')]['order'] = [
                            'href' => $orderUrl,
                            'label' => __('View Order')
                        ];
                    }
                }
            }
        }

        return $dataSource;
    }
}
