<?php

namespace Shopthru\Connector\Api\Data;

use Magento\Framework\DataObject;

interface CancelOrderRequestInterface
{
    public const REASON_CODE = 'reason_code';

    public const REASON_TEXT = 'reason_text';

    public const CANCEL_DATA = 'cancel_data';

    /**
     * Get Reason Code
     *
     * @return string
     */
    public function getReasonCode(): string;

    /**
     * Set Reason Code
     *
     * @param string $reasonCode
     * @return $this
     */
    public function setReasonCode(string $reasonCode): self;

    /**
     * Get Reason Text
     *
     * @return string|null
     */
    public function getReasonText(): ?string;

    /**
     * Set Reason Text
     *
     * @param string $reasonText
     * @return $this
     */
    public function setReasonText(string $reasonText): self;

    /**
     * Get Cancel Data
     *
     * @return array|null
     */
    public function getCancelData(): ?array;

    /**
     * Set Cancel Data
     *
     * @param array $cancelData
     * @return $this
     */
    public function setCancelData(array $cancelData): self;

}


