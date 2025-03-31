<?php
namespace Shopthru\Connector\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Shopthru\Connector\Api\Data\ImportLogInterface;
use Shopthru\Connector\Api\ImportLogRepositoryInterface;
use Shopthru\Connector\Model\Config;
use Shopthru\Connector\Model\EventType;
use Shopthru\Connector\Model\ImportLogFactory;

class Data extends AbstractHelper
{
    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param DateTime $dateTime
     * @param Json $json
     * @param Config $config
     * @param ImportLogFactory $importLogFactory
     * @param ImportLogRepositoryInterface $importLogRepository
     */
    public function __construct(
        Context $context,
        private readonly StoreManagerInterface $storeManager,
        private readonly DateTime $dateTime,
        private readonly Json $json,
        private readonly Config $config,
        private readonly ImportLogFactory $importLogFactory,
        private readonly ImportLogRepositoryInterface $importLogRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Get order repository
     *
     * @return ImportLogRepositoryInterface
     */
    public function getImportLogRepository(): ImportLogRepositoryInterface
    {
        return $this->importLogRepository;
    }

    /**
     * Create import log entry
     *
     * @param string $shopthruOrderId
     * @param string|null $shopthruPublisherRef
     * @param string|null $shopthruPublisherName
     * @param string $status
     * @param array|null $shopthruData
     * @param array|null $additionalData
     * @param string|null $failedReason
     * @param string|null $magentoOrderId
     * @param int|null $parentImportId
     * @return \Shopthru\Connector\Api\Data\ImportLogInterface
     */
    public function createImportLog(
        string $shopthruOrderId,
        ?string $shopthruPublisherRef = null,
        ?string $shopthruPublisherName = null,
        string $status = ImportLogInterface::STATUS_PENDING,
        ?array $shopthruData = null,
        ?array $additionalData = null,
        ?string $failedReason = null,
        ?string $magentoOrderId = null,
        ?int $parentImportId = null
    ): ImportLogInterface {
        $importLog = $this->importLogFactory->create();
        $importLog->setShopthruOrderId($shopthruOrderId);
        $importLog->setShopthruPublisherRef($shopthruPublisherRef);
        $importLog->setShopthruPublisherName($shopthruPublisherName);
        $importLog->setStatus($status);
        $importLog->setShopthruData($shopthruData); // Store original Shopthru data
        $importLog->setLogData([]); // Initialize empty array for event logs
        $importLog->setAdditionalData($additionalData);
        $importLog->setFailedReason($failedReason);
        $importLog->setMagentoOrderId($magentoOrderId);
        $importLog->setParentImportId($parentImportId);

        if ($status === ImportLogInterface::STATUS_SUCCESS) {
            $importLog->setImportedAt($this->dateTime->gmtDate());
        }

        try {
            $this->importLogRepository->save($importLog);
        } catch (\Exception $e) {
            $this->_logger->critical('Error saving import log: ' . $e->getMessage());
        }

        return $importLog;
    }

    /**
     * Add an event to the import log
     *
     * @param int $importId
     * @param string $eventName
     * @param string|null $description
     * @param array|null $additionalData
     * @return \Shopthru\Connector\Api\Data\ImportLogInterface|null
     */
    public function addEventLog(
        int $importId,
        string $eventName,
        ?string $description = null,
        ?array $additionalData = null
    ): ?ImportLogInterface {
        try {
            $importLog = $this->importLogRepository->getById($importId);

            // Get existing log data or initialize empty array
            $logData = $importLog->getLogData();
            if (!is_array($logData)) {
                $logData = [];
            }

            // Create new event entry
            $event = [
                'event' => $eventName,
                'datetime' => $this->dateTime->gmtDate('Y-m-d H:i:s')
            ];

            // Add optional fields if provided
            if ($description !== null) {
                $event['description'] = $description;
            }

            if ($additionalData !== null) {
                $event['additional_data'] = $additionalData;
            }

            // Add event to log data
            $logData[] = $event;

            // Save updated log data
            $importLog->setLogData($logData);
            $this->importLogRepository->save($importLog);

            return $importLog;
        } catch (\Exception $e) {
            $this->_logger->critical('Error adding event log: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update import log entry
     *
     * @param int $importId
     * @param string|null $status
     * @param array|null $additionalData
     * @param string|null $failedReason
     * @param string|null $magentoOrderId
     * @return \Shopthru\Connector\Api\Data\ImportLogInterface|null
     */
    public function updateImportLog(
        int $importId,
        ?string $status = null,
        ?array $additionalData = null,
        ?string $failedReason = null,
        ?string $magentoOrderId = null
    ): ?ImportLogInterface {
        try {
            $importLog = $this->importLogRepository->getById($importId);

            if ($status !== null) {
                $importLog->setStatus($status);

                if ($status === ImportLogInterface::STATUS_SUCCESS) {
                    $importLog->setImportedAt($this->dateTime->gmtDate());
                }
            }

            if ($additionalData !== null) {
                $importLog->setAdditionalData($additionalData);
            }

            if ($failedReason !== null) {
                $importLog->setFailedReason($failedReason);
            }

            if ($magentoOrderId !== null) {
                $importLog->setMagentoOrderId($magentoOrderId);
            }

            $this->importLogRepository->save($importLog);

            return $importLog;
        } catch (\Exception $e) {
            $this->_logger->critical('Error updating import log: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get store by ID
     *
     * @param int|null $storeId
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStore(?int $storeId = null): \Magento\Store\Api\Data\StoreInterface
    {
        return $this->storeManager->getStore($storeId);
    }

    /**
     * Format price based on store locale
     *
     * @param float $price
     * @param int|null $storeId
     * @return float
     */
    public function formatPrice(float $price, ?int $storeId = null): float
    {
        // Shopthru sends prices in minor units (cents, pence, etc.)
        // Convert to standard currency format
        return $price;
        return $price / 100;
    }

    /**
     * Log debug message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logDebug(string $message, array $context = []): void
    {
        $this->_logger->debug($message, $context);
    }

    /**
     * Log error message
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logError(string $message, array $context = []): void
    {
        $this->_logger->error($message, $context);
    }
}
