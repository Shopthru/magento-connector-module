<?php
namespace Shopthru\Connector\Api\Data;

interface ImportLogInterface
{
    /**
     * Constants for keys of data array
     */
    public const IMPORT_ID = 'import_id';
    public const PARENT_IMPORT_ID = 'parent_import_id';
    public const SHOPTHRU_ORDER_ID = 'shopthru_order_id';
    public const SHOPTHRU_PUBLISHER_REF = 'shopthru_publisher_ref';
    public const SHOPTHRU_PUBLISHER_NAME = 'shopthru_publisher_name';
    public const STATUS = 'status';
    public const IMPORTED_AT = 'imported_at';
    public const CREATED_AT = 'created_at';
    public const LOG_DATA = 'log_data';
    public const SHOPTHRU_DATA = 'shopthru_data';
    public const ADDITIONAL_DATA = 'additional_data';
    public const FAILED_REASON = 'failed_reason';
    public const MAGENTO_ORDER_ID = 'magento_order_id';

    /**
     * Status Constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    /**
     * Get Import ID
     *
     * @return int|null
     */
    public function getImportId(): ?int;

    /**
     * Set Import ID
     *
     * @param int $importId
     * @return $this
     */
    public function setImportId(int $importId): self;

    /**
     * Get Parent Import ID
     *
     * @return int|null
     */
    public function getParentImportId(): ?int;

    /**
     * Set Parent Import ID
     *
     * @param int|null $parentImportId
     * @return $this
     */
    public function setParentImportId(?int $parentImportId): self;

    /**
     * Get Shopthru Order ID
     *
     * @return string
     */
    public function getShopthruOrderId(): string;

    /**
     * Set Shopthru Order ID
     *
     * @param string $shopthruOrderId
     * @return $this
     */
    public function setShopthruOrderId(string $shopthruOrderId): self;

    /**
     * Get Shopthru Publisher Reference
     *
     * @return string|null
     */
    public function getShopthruPublisherRef(): ?string;

    /**
     * Set Shopthru Publisher Reference
     *
     * @param string|null $shopthruPublisherRef
     * @return $this
     */
    public function setShopthruPublisherRef(?string $shopthruPublisherRef): self;

    /**
     * Get Shopthru Publisher Name
     *
     * @return string|null
     */
    public function getShopthruPublisherName(): ?string;

    /**
     * Set Shopthru Publisher Name
     *
     * @param string|null $shopthruPublisherName
     * @return $this
     */
    public function setShopthruPublisherName(?string $shopthruPublisherName): self;

    /**
     * Get Status
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Set Status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self;

    /**
     * Get Imported At
     *
     * @return string|null
     */
    public function getImportedAt(): ?string;

    /**
     * Set Imported At
     *
     * @param string|null $importedAt
     * @return $this
     */
    public function setImportedAt(?string $importedAt): self;

    /**
     * Get Created At
     *
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * Set Created At
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * Get Log Data
     *
     * @return array|null
     */
    public function getLogData(): ?array;

    /**
     * Set Log Data
     *
     * @param array|string|null $logData
     * @return $this
     */
    public function setLogData(array|string|null $logData): self;

    /**
     * Get Shopthru Data
     *
     * @return array|null
     */
    public function getShopthruData(): ?array;

    /**
     * Set Shopthru Data
     *
     * @param array|string|null $shopthruData
     * @return $this
     */
    public function setShopthruData(array|string|null $shopthruData): self;

    /**
     * Get Additional Data
     *
     * @return array|null
     */
    public function getAdditionalData(): ?array;

    /**
     * Set Additional Data
     *
     * @param array|string|null $additionalData
     * @return $this
     */
    public function setAdditionalData(array|string|null $additionalData): self;

    /**
     * Get Failed Reason
     *
     * @return string|null
     */
    public function getFailedReason(): ?string;

    /**
     * Set Failed Reason
     *
     * @param string|null $failedReason
     * @return $this
     */
    public function setFailedReason(?string $failedReason): self;

    /**
     * Get Magento Order ID
     *
     * @return string|null
     */
    public function getMagentoOrderId(): ?string;

    /**
     * Set Magento Order ID
     *
     * @param string|null $magentoOrderId
     * @return $this
     */
    public function setMagentoOrderId(?string $magentoOrderId): self;
}
