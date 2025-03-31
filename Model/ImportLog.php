<?php
namespace Shopthru\Connector\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Shopthru\Connector\Api\Data\ImportLogInterface;

class ImportLog extends AbstractModel implements ImportLogInterface
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param Json $serializer
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        private readonly Json $serializer,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\ImportLog::class);
    }

    /**
     * @inheritDoc
     */
    public function getImportId(): ?int
    {
        $id = $this->getData(self::IMPORT_ID);
        return $id !== null ? (int)$id : null;
    }

    /**
     * @inheritDoc
     */
    public function setImportId(int $importId): self
    {
        return $this->setData(self::IMPORT_ID, $importId);
    }

    /**
     * @inheritDoc
     */
    public function getParentImportId(): ?int
    {
        $id = $this->getData(self::PARENT_IMPORT_ID);
        return $id !== null ? (int)$id : null;
    }

    /**
     * @inheritDoc
     */
    public function setParentImportId(?int $parentImportId): self
    {
        return $this->setData(self::PARENT_IMPORT_ID, $parentImportId);
    }

    /**
     * @inheritDoc
     */
    public function getShopthruOrderId(): string
    {
        return (string)$this->getData(self::SHOPTHRU_ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setShopthruOrderId(string $shopthruOrderId): self
    {
        return $this->setData(self::SHOPTHRU_ORDER_ID, $shopthruOrderId);
    }

    /**
     * @inheritDoc
     */
    public function getShopthruPublisherRef(): ?string
    {
        return $this->getData(self::SHOPTHRU_PUBLISHER_REF);
    }

    /**
     * @inheritDoc
     */
    public function setShopthruPublisherRef(?string $shopthruPublisherRef): self
    {
        return $this->setData(self::SHOPTHRU_PUBLISHER_REF, $shopthruPublisherRef);
    }

    /**
     * @inheritDoc
     */
    public function getShopthruPublisherName(): ?string
    {
        return $this->getData(self::SHOPTHRU_PUBLISHER_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setShopthruPublisherName(?string $shopthruPublisherName): self
    {
        return $this->setData(self::SHOPTHRU_PUBLISHER_NAME, $shopthruPublisherName);
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): string
    {
        return (string)$this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus(string $status): self
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getImportedAt(): ?string
    {
        return $this->getData(self::IMPORTED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setImportedAt(?string $importedAt): self
    {
        return $this->setData(self::IMPORTED_AT, $importedAt);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): string
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt(string $createdAt): self
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getLogData(): ?array
    {
        $logData = $this->getData(self::LOG_DATA);
        if ($logData && is_string($logData)) {
            return $this->serializer->unserialize($logData);
        }
        return $logData ?: [];
    }

    /**
     * @inheritDoc
     */
    public function setLogData(array|string|null $logData): self
    {
        if (is_array($logData)) {
            $logData = $this->serializer->serialize($logData);
        }
        return $this->setData(self::LOG_DATA, $logData);
    }

    /**
     * @inheritDoc
     */
    public function getShopthruData(): ?array
    {
        $shopthruData = $this->getData(self::SHOPTHRU_DATA);
        if ($shopthruData && is_string($shopthruData)) {
            return $this->serializer->unserialize($shopthruData);
        }
        return $shopthruData;
    }

    /**
     * @inheritDoc
     */
    public function setShopthruData(array|string|null $shopthruData): self
    {
        if (is_array($shopthruData)) {
            $shopthruData = $this->serializer->serialize($shopthruData);
        }
        return $this->setData(self::SHOPTHRU_DATA, $shopthruData);
    }

    /**
     * @inheritDoc
     */
    public function getAdditionalData(): ?array
    {
        $additionalData = $this->getData(self::ADDITIONAL_DATA);
        if ($additionalData && is_string($additionalData)) {
            return $this->serializer->unserialize($additionalData);
        }
        return $additionalData;
    }

    /**
     * @inheritDoc
     */
    public function setAdditionalData(array|string|null $additionalData): self
    {
        if (is_array($additionalData)) {
            $additionalData = $this->serializer->serialize($additionalData);
        }
        return $this->setData(self::ADDITIONAL_DATA, $additionalData);
    }

    /**
     * @inheritDoc
     */
    public function getFailedReason(): ?string
    {
        return $this->getData(self::FAILED_REASON);
    }

    /**
     * @inheritDoc
     */
    public function setFailedReason(?string $failedReason): self
    {
        return $this->setData(self::FAILED_REASON, $failedReason);
    }

    /**
     * @inheritDoc
     */
    public function getMagentoOrderId(): ?string
    {
        return $this->getData(self::MAGENTO_ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setMagentoOrderId(?string $magentoOrderId): self
    {
        return $this->setData(self::MAGENTO_ORDER_ID, $magentoOrderId);
    }
}
