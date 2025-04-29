<?php

namespace Shopthru\Connector\Model;

use Magento\Framework\DataObject;
use Shopthru\Connector\Api\Data\ConfirmOrderInterface;

class ConfirmOrder extends DataObject implements ConfirmOrderInterface
{
    /**
     * @inheritDoc
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getPaymentData($key=null, $default=null)
    {
        $paymentData = $this->getData(self::PAYMENT_DATA);
        if (!is_array($paymentData)) {
            $paymentData = [];
        }
        $paymentData = new DataObject($paymentData);
        if ($key === null) {
            return $paymentData;
        }
        return $paymentData->hasData($key) ? $this->getData($key) : $default;
    }

    /**
     * @inheritDoc
     */
    public function setPaymentData($paymentData)
    {
        return $this->setData(self::PAYMENT_DATA, $paymentData);
    }

    /**
     * @inheritDoc
     */
    public function getOrderData()
    {
        return $this->getData(self::ORDER_DATA);
    }

    /**
     * @inheritDoc
     */
    public function setOrderData($orderData)
    {
        return $this->setData(self::ORDER_DATA, $orderData);
    }

    /**
     * @inheritDoc
     */
    public function getOrderNote()
    {
        return $this->getData(self::ORDER_NOTE);
    }

    /**
     * @inheritDoc
     */
    public function setOrderNote($orderNote)
    {
        return $this->setData(self::ORDER_NOTE, $orderNote);
    }
}
