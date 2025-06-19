<?php

namespace Shopthru\Connector\Api\Data;

use Magento\Framework\DataObject;

interface ConfirmOrderRequestInterface
{
    public const ORDER_ID = 'order_id';

    public const TRANSACTION_ID = 'transaction_id';

    public const PAYMENT_DATA = 'payment_data';
    public const ORDER_DATA = 'order_data';

    public const ORDER_NOTE = 'order_note';

    /**
     * Get Order ID
     *
     * @return string
     */
    public function getOrderId();

    /**
     * Set Order ID
     *
     * @param string $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * Get Payment Data
     *
     * @return mixed
     */
    public function getPaymentData($key=null, $default=null);

    /**
     * Set Payment Data
     *
     * @param array $paymentData
     * @return $this
     */
    public function setPaymentData($paymentData);

    /**
     * Get Order Data
     *
     * @return array
     */
    public function getOrderData();

    /**
     * Set Order Data
     *
     * @param array $orderData
     * @return $this
     */
    public function setOrderData($orderData);

    /**
     * Get Order Note
     *
     * @return string
     */
    public function getOrderNote();

    /**
     * Set Order Note
     *
     * @param string $orderNote
     * @return $this
     */
    public function setOrderNote($orderNote);

    /**
     * @return mixed
     */
    public function getTransactionId();

    /**
     * Set Transaction ID
     *
     * @param string $transactionId
     * @return $this
     */
    public function setTransactionId($transactionId);

}


