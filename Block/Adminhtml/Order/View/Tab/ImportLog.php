<?php

namespace Shopthru\Connector\Block\Adminhtml\Order\View\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;

use Shopthru\Connector\Helper\Logging as LoggingHelper;

class ImportLog extends Template implements TabInterface
{
    protected $_template = "Shopthru_Connector::order/import/view.phtml";

    public function __construct(
        Context $context,
        private readonly Registry $registry,
        private readonly LoggingHelper $loggingHelper,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    public function getImportLog()
    {
        $log = $this->coreRegistry->registry('current_import_log');
        if ($log) {
            return $log;
        }

        //if we don't have a log in the registry then see if we have one based on the current order loaded
        $currentOrder = $this->coreRegistry->registry('current_order') ?? $this->coreRegistry->registry('sales_order') ?? null;
        if ($currentOrder) {
            try {
                $log = $this->loggingHelper->getLogByMagentoOrderId($currentOrder->getId());
            }catch (NoSuchEntityException $e){
                return null;
            }
            $this->coreRegistry->register('current_import_log', $log);
            return $log;
        }
        return null;

    }

    public function formatJson($data)
    {
        if (!$data) {
            return '';
        }

        if (is_string($data)) {
            $data = $this->jsonSerializer->unserialize($data); // @phpstan-ignore-line
        }

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Retrieve order model instance
     *
     * @return Order
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * @inheritDoc
     */
    public function getTabLabel()
    {
        return __('Shopthru Order');
    }

    /**
     * @inheritDoc
     */
    public function getTabTitle()
    {
        return __('Shopthru Order');
    }

    /**
     * @inheritDoc
     */
    public function canShowTab()
    {
        if ($this->getImportLog()) {
            return true;
        }

        return false;

    }

    /**
     * @inheritDoc
     */
    public function isHidden()
    {
        if ($this->getImportLog()) {
            return false;
        }
        return true;
    }
}
