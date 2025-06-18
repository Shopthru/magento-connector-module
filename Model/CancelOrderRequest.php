<?php

namespace Shopthru\Connector\Model;

use Magento\Framework\DataObject;
use Shopthru\Connector\Api\Data\CancelOrderRequestInterface;

class CancelOrderRequest extends DataObject implements CancelOrderRequestInterface
{
    /**
     * @inheritDoc
     */
    public function getCancelData(): ?array
    {
        return $this->getData(self::CANCEL_DATA);
    }

    /**
     * @inheritDoc
     */
    public function setCancelData(array $cancelData): self
    {
        return $this->setData(self::CANCEL_DATA, $cancelData);
    }

    /**
     * @inheritDoc
     */
    public function getReasonCode(): string
    {
        return $this->getData(self::REASON_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setReasonCode(string $reasonCode): self
    {
        return $this->setData(self::REASON_CODE, $reasonCode);
    }

    /**
     * @inheritDoc
     */
    public function getReasonText(): ?string
    {
        return $this->getData(self::REASON_TEXT);
    }

    /**
     * @inheritDoc
     */
    public function setReasonText(?string $reasonText): self
    {
        return $this->setData(self::REASON_TEXT, $reasonText);
    }
}
