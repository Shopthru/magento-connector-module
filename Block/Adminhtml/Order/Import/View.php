<?php
/**
 * View.php
 */
namespace Shopthru\Connector\Block\Adminhtml\Order\Import;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;

use Shopthru\Connector\Helper\Logging as LoggingHelper;

class View extends Template
{
    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param Json $jsonSerializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        private Registry $coreRegistry,
        private Json $jsonSerializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get current import log
     *
     * @return \Shopthru\Connector\Api\Data\ImportLogInterface
     */
    public function getImportLog()
    {
        return $this->coreRegistry->registry('current_import_log');
    }

    /**
     * Format log data or additional data as JSON
     *
     * @param string|array $data
     * @return string
     */
    public function formatJson($data)
    {
        if (!$data) {
            return '';
        }

        if (is_string($data)) {
            $data = $this->jsonSerializer->unserialize($data);
        }

        return json_encode($data, JSON_PRETTY_PRINT);
    }
}
