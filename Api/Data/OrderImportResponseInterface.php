<?php

namespace Shopthru\Connector\Api\Data;

interface OrderImportResponseInterface
{

    public const IMPORT_ACTION = 'import_action';
    public const IMPORT_ACTION_SUCCESS = 'import_action_success';

    public const SHOPTHRU_ORDER_ID = 'shopthru_order_id';

    public const IMPORT_LOG_ID = 'import_log_id';
    public const MAGENTO_ORDER_ID = 'magento_order_id';
    public const IMPORT_STATUS = 'import_status';
    public const ERROR_CODE = 'error_code';
    public const ERROR_MESSAGE = 'error_message';
    public const MAGENTO_ORDER_STATUS = 'magento_order_status';
    public const MESSAGE = 'message';
    public const IMPORT_LOG = 'import_log';

    public const IMPORT_ACTION_CREATE = 'create';
    public const IMPORT_ACTION_CONFIRM = 'confirm';
    public const IMPORT_ACTION_CANCEL = 'cancel';

    /**
     * @return string
     */
    public function getImportAction(): string;

    /**
     * @param string $importAction
     * @return self
     */
    public function setImportAction(string $importAction): self;

    /**
     * @param bool $importActionSuccess
     * @return self
     */
    public function setImportActionSuccess(bool $importActionSuccess): self;

    /**
     * @return bool
     */
    public function getImportActionSuccess(): bool;

    /**
     * Get Shopthru Order ID
     *
     * @return string
     */
    public function getShopthruOrderId(): ?string;

    /**
     * Set Shopthru Order ID
     *
     * @param string $shopthruOrderId
     * @return $this
     */
    public function setShopthruOrderId(string $shopthruOrderId): self;

    /**
     * Get Import Log ID
     *
     * @return int|null
     */
    public function getImportLogId(): ?int;

    /**
     * Set Import Log ID
     *
     * @param int $importLogId
     * @return $this
     */
    public function setImportLogId(int $importLogId): self;

    /**
     * Get Magento Order ID
     *
     * @return string|int|null
     */
    public function getMagentoOrderId(): string|int|null;

    /**
     * Set Magento Order ID
     *
     * @param string|int $magentoOrderId
     * @return $this
     */
    public function setMagentoOrderId(string|int $magentoOrderId): self;

    /**
     * Get Import Status
     *
     * @return ?string
     */
    public function getImportStatus(): ?string;

    /**
     * Set Import Status
     *
     * @param string $importStatus
     * @return $this
     */
    public function setImportStatus(string $importStatus): self;

    /**
     * @return string|null
     */
    public function getErrorCode(): ?string;

    /**
     * @param string $errorCode
     * @return $this
     */
    public function setErrorCode(string $errorCode): self;

    /**
     * @return string|null
     */
    public function getErrorMessage(): ?string;

    /**
     * @param string $errorMessage
     * @return $this
     */
    public function setErrorMessage(string $errorMessage): self;

    /**
     * @return string|null
     */
    public function getMagentoOrderStatus(): ?string;

    /**
     * @param string $magentoOrderStatus
     * @return $this
     */
    public function setMagentoOrderStatus(string $magentoOrderStatus): self;

    /**
     * @return string|null
     */
    public function getMessage(): ?string;

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): self;

    /**
     * @return \Shopthru\Connector\Api\Data\ImportLogInterface|null
     */
    public function getImportLog(): ?ImportLogInterface;

    /**
     * @param \Shopthru\Connector\Api\Data\ImportLogInterface $importLog
     * @return $this
     */
    public function setImportLog(ImportLogInterface $importLog): self;

}
