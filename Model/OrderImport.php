<?php

namespace Shopthru\Connector\Model;

use Magento\Framework\DataObject;
use Shopthru\Connector\Api\Data\OrderImportInterface;

class OrderImport extends DataObject implements OrderImportInterface
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
    public function getPublisher()
    {
        return $this->getData(self::PUBLISHER);
    }

    /**
     * @inheritDoc
     */
    public function setPublisher($publisher)
    {
        return $this->setData(self::PUBLISHER, $publisher);
    }

    /**
     * @inheritDoc
     */
    public function getPrimaryShortCode()
    {
        return $this->getData(self::PRIMARY_SHORT_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setPrimaryShortCode($primaryShortCode)
    {
        return $this->setData(self::PRIMARY_SHORT_CODE, $primaryShortCode);
    }

    /**
     * @inheritDoc
     */
    public function getPrimarySku()
    {
        return $this->getData(self::PRIMARY_SKU);
    }

    /**
     * @inheritDoc
     */
    public function setPrimarySku($primarySku)
    {
        return $this->setData(self::PRIMARY_SKU, $primarySku);
    }

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getCurrency()
    {
        return $this->getData(self::CURRENCY);
    }

    /**
     * @inheritDoc
     */
    public function setCurrency($currency)
    {
        return $this->setData(self::CURRENCY, $currency);
    }

    /**
     * @inheritDoc
     */
    public function getLocale()
    {
        return $this->getData(self::LOCALE);
    }

    /**
     * @inheritDoc
     */
    public function setLocale($locale)
    {
        return $this->setData(self::LOCALE, $locale);
    }

    /**
     * @inheritDoc
     */
    public function getSubTotal()
    {
        return $this->getData(self::SUB_TOTAL);
    }

    /**
     * @inheritDoc
     */
    public function setSubTotal($subTotal)
    {
        return $this->setData(self::SUB_TOTAL, $subTotal);
    }

    /**
     * @inheritDoc
     */
    public function getTotalPaid()
    {
        return $this->getData(self::TOTAL_PAID);
    }

    /**
     * @inheritDoc
     */
    public function setTotalPaid($totalPaid)
    {
        return $this->setData(self::TOTAL_PAID, $totalPaid);
    }

    /**
     * @inheritDoc
     */
    public function getTotal()
    {
        return $this->getData(self::TOTAL);
    }

    /**
     * @inheritDoc
     */
    public function setTotal($total)
    {
        return $this->setData(self::TOTAL, $total);
    }

    /**
     * @inheritDoc
     */
    public function getShippingTotal()
    {
        return $this->getData(self::SHIPPING_TOTAL);
    }

    /**
     * @inheritDoc
     */
    public function setShippingTotal($shippingTotal)
    {
        return $this->setData(self::SHIPPING_TOTAL, $shippingTotal);
    }

    /**
     * @inheritDoc
     */
    public function getPurchaseUrl()
    {
        return $this->getData(self::PURCHASE_URL);
    }

    /**
     * @inheritDoc
     */
    public function setPurchaseUrl($purchaseUrl)
    {
        return $this->setData(self::PURCHASE_URL, $purchaseUrl);
    }

    /**
     * @inheritDoc
     */
    public function getDiscountTotal()
    {
        return $this->getData(self::DISCOUNT_TOTAL);
    }

    /**
     * @inheritDoc
     */
    public function setDiscountTotal($discountTotal)
    {
        return $this->setData(self::DISCOUNT_TOTAL, $discountTotal);
    }

    /**
     * @inheritDoc
     */
    public function getCommissionTotal()
    {
        return $this->getData(self::COMMISSION_TOTAL);
    }

    /**
     * @inheritDoc
     */
    public function setCommissionTotal($commissionTotal)
    {
        return $this->setData(self::COMMISSION_TOTAL, $commissionTotal);
    }

    /**
     * @inheritDoc
     */
    public function getDiscountCodesApplied()
    {
        return $this->getData(self::DISCOUNT_CODES_APPLIED);
    }

    /**
     * @inheritDoc
     */
    public function setDiscountCodesApplied($discountCodesApplied)
    {
        return $this->setData(self::DISCOUNT_CODES_APPLIED, $discountCodesApplied);
    }

    /**
     * @inheritDoc
     */
    public function getCheckoutDomain()
    {
        return $this->getData(self::CHECKOUT_DOMAIN);
    }

    /**
     * @inheritDoc
     */
    public function setCheckoutDomain($checkoutDomain)
    {
        return $this->setData(self::CHECKOUT_DOMAIN, $checkoutDomain);
    }

    /**
     * @inheritDoc
     */
    public function getExtStoreId()
    {
        return $this->getData(self::EXT_STORE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setExtStoreId($extStoreId)
    {
        return $this->setData(self::EXT_STORE_ID, $extStoreId);
    }

    /**
     * @inheritDoc
     */
    public function getExtAttributes()
    {
        return $this->getData(self::EXT_ATTRIBUTES);
    }

    /**
     * @inheritDoc
     */
    public function setExtAttributes($extAttributes)
    {
        return $this->setData(self::EXT_ATTRIBUTES, $extAttributes);
    }

    /**
     * @inheritDoc
     */
    public function getPaymentMethod()
    {
        return $this->getData(self::PAYMENT_METHOD);
    }

    /**
     * @inheritDoc
     */
    public function setPaymentMethod($paymentMethod)
    {
        return $this->setData(self::PAYMENT_METHOD, $paymentMethod);
    }

    /**
     * @inheritDoc
     */
    public function getPaymentTransactionId()
    {
        return $this->getData(self::PAYMENT_TRANSACTION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setPaymentTransactionId($paymentTransactionId)
    {
        return $this->setData(self::PAYMENT_TRANSACTION_ID, $paymentTransactionId);
    }

    /**
     * @inheritDoc
     */
    public function getPaymentData()
    {
        return $this->getData(self::PAYMENT_DATA);
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
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getShippingMethod()
    {
        return $this->getData(self::SHIPPING_METHOD);
    }

    /**
     * @inheritDoc
     */
    public function setShippingMethod($shippingMethod)
    {
        return $this->setData(self::SHIPPING_METHOD, $shippingMethod);
    }

    /**
     * @inheritDoc
     */
    public function getShippingTitle()
    {
        return $this->getData(self::SHIPPING_TITLE);
    }

    /**
     * @inheritDoc
     */
    public function setShippingTitle($shippingTitle)
    {
        return $this->setData(self::SHIPPING_TITLE, $shippingTitle);
    }

    /**
     * @inheritDoc
     */
    public function getItems()
    {
        return $this->getData(self::ITEMS);
    }

    /**
     * @inheritDoc
     */
    public function setItems($items)
    {
        return $this->setData(self::ITEMS, $items);
    }

    /**
     * @inheritDoc
     */
    public function getCustomer()
    {
        return $this->getData(self::CUSTOMER);
    }

    /**
     * @inheritDoc
     */
    public function setCustomer($customer)
    {
        return $this->setData(self::CUSTOMER, $customer);
    }
}
