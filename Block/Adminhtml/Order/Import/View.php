<?php
/**
 * View.php
 */
namespace Shopthru\Connector\Block\Adminhtml\Order\Import;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;

class View extends Template
{
    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param Json $jsonSerializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        Json $jsonSerializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->coreRegistry = $coreRegistry;
        $this->jsonSerializer = $jsonSerializer;
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
     * Format date
     *
     * @param string $date
     * @return string
     */
//    public function formatDate($date)
//    {
//        if (!$date) {
//            return '';
//        }
//
//        return $this->_localeDate->formatDateTime(
//            $date,
//            \IntlDateFormatter::MEDIUM,
//            \IntlDateFormatter::MEDIUM
//        );
//    }
//
//    public function formatDate(
//        $date = null,
//        $format = \IntlDateFormatter::SHORT,
//        $showTime = false,
//        $timezone = null
//    ) {
//        $date = $date instanceof \DateTimeInterface ? $date : new \DateTime($date ?? 'now');
//        return $this->_localeDate->formatDateTime(
//            $date,
//            $format,
//            $showTime ? $format : \IntlDateFormatter::NONE,
//            null,
//            $timezone
//        );
//    }

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
