<?php

namespace Shopthru\Connector\Model;

use Magento\Framework\DataObject;
use Shopthru\Connector\Api\Data\ImportLogInterface;
use Shopthru\Connector\Api\Data\OrderImportResponseInterface;
use Shopthru\Connector\Api\ImportLogRepositoryInterface;

class OrderImportResponse extends DataObject implements OrderImportResponseInterface
{
    public function __construct(
        private readonly ImportLogRepositoryInterface $importLogRepository, array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * @inheritDoc
     */
    public function getShopthruOrderId(): string
    {
        return $this->getData(self::SHOPTHRU_ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setShopthruOrderId(string $shopthruOrderId): self
    {
        return $this->setData(self::SHOPTHRU_ORDER_ID, $shopthruOrderId);
    }

    public function getImportAction(): string
    {
        return $this->getData(self::IMPORT_ACTION);
    }

    public function setImportAction(string $importAction): self
    {
        return $this->setData(self::IMPORT_ACTION, $importAction);
    }

    public function setImportActionSuccess(bool $importActionSuccess): self
    {
        return $this->setData(self::IMPORT_ACTION_SUCCESS, $importActionSuccess);
    }

    public function getImportActionSuccess(): bool
    {
        return $this->getData(self::IMPORT_ACTION_SUCCESS);
    }

    /**
     * @inheritDoc
     */
    public function getImportLogId(): ?int
    {
        return $this->getData(self::IMPORT_LOG_ID);
    }

    /**
     * @inheritDoc
     */
    public function setImportLogId(int $importLogId): self
    {
        return $this->setData(self::IMPORT_LOG_ID, $importLogId);
    }

    /**
     * @inheritDoc
     */
    public function getMagentoOrderId(): string|int|null
    {
        return $this->getData(self::MAGENTO_ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setMagentoOrderId(int|string $magentoOrderId): self
    {
        return $this->setData(self::MAGENTO_ORDER_ID, $magentoOrderId);
    }

    /**
     * @inheritDoc
     */
    public function getErrorCode(): ?string
    {
        return $this->getData(self::ERROR_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setErrorCode(string $errorCode): self
    {
        return $this->setData(self::ERROR_CODE, $errorCode);
    }

    /**
     * @inheritDoc
     */
    public function getMagentoOrderStatus(): ?string
    {
        return $this->getData(self::MAGENTO_ORDER_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setMagentoOrderStatus(string $magentoOrderStatus): self
    {
        return $this->setData(self::MAGENTO_ORDER_STATUS, $magentoOrderStatus);
    }

    /**
     * @inheritDoc
     */
    public function getImportStatus(): ?string
    {
        return $this->getData(self::IMPORT_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setImportStatus(string $importStatus): self
    {
        return $this->setData(self::IMPORT_STATUS, $importStatus);
    }

    /**
     * @inheritDoc
     */
    public function getMessage(): ?string
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * @inheritDoc
     */
    public function setMessage(string $message): self
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * @inheritDoc
     */
    public function getErrorMessage(): ?string
    {
        return $this->getData(self::ERROR_MESSAGE);
    }

    /**
     * @inheritDoc
     */
    public function setErrorMessage(string $errorMessage): self
    {
        return $this->setData(self::ERROR_MESSAGE, $errorMessage);
    }

    /**
     * @inheritDoc
     */
    public function getImportLog(): ?ImportLogInterface
    {
        if (!$this->getData(self::IMPORT_LOG) && $this->getImportLogId()) {
            $this->setImportLog($this->importLogRepository->getById($this->getImportLogId()));
        }

        return $this->getData(self::IMPORT_LOG);
    }

    /**
     * @inheritDoc
     */
    public function setImportLog(ImportLogInterface $importLog): self
    {
        return $this->setData(self::IMPORT_LOG, $importLog);
    }

}
