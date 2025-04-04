<?php

namespace Shopthru\Connector\Api\Data;

interface OrderImportInterface
{
    public const ORDER_ID = 'order_id';
    public const PUBLISHER = 'publisher';
    public const PRIMARY_SHORT_CODE = 'primary_short_code';
    public const PRIMARY_SKU = 'primary_sku';
    public const STATUS = 'status';
    public const CURRENCY = 'currency';
    public const LOCALE = 'locale';
    public const SUB_TOTAL = 'sub_total';
    public const TOTAL_PAID = 'total_paid';
    public const TOTAL = 'total';
    public const SHIPPING_TOTAL = 'shipping_total';
    public const PURCHASE_URL = 'purchase_url';
    public const DISCOUNT_TOTAL = 'discount_total';
    public const COMMISSION_TOTAL = 'commission_total';
    public const DISCOUNT_CODES_APPLIED = 'discount_codes_applied';
    public const CHECKOUT_DOMAIN = 'checkout_domain';
    public const EXT_STORE_ID = 'ext_store_id';
    public const EXT_ATTRIBUTES = 'ext_attributes';
    public const PAYMENT_METHOD = 'payment_method';
    public const PAYMENT_TRANSACTION_ID = 'payment_transaction_id';
    public const PAYMENT_DATA = 'payment_data';
    public const CREATED_AT = 'created_at';
    public const SHIPPING_METHOD = 'shipping_method';
    public const SHIPPING_TITLE = 'shipping_title';
    public const ITEMS = 'items';
    public const CUSTOMER = 'customer';
    public const TAX_TOTAL = 'tax_total';
    public const TOTAL_EX_TAX = 'total_excl_tax';

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
     * Get Publisher
     *
     * @return mixed
     */
    public function getPublisher();

    /**
     * Set Publisher
     *
     * @param mixed $publisher
     * @return $this
     */
    public function setPublisher($publisher);

    /**
     * Get Primary Short Code
     *
     * @return string|null
     */
    public function getPrimaryShortCode();

    /**
     * Set Primary Short Code
     *
     * @param string $primaryShortCode
     * @return $this
     */
    public function setPrimaryShortCode($primaryShortCode);

    /**
     * Get Primary SKU
     *
     * @return string|null
     */
    public function getPrimarySku();

    /**
     * Set Primary SKU
     *
     * @param string $primarySku
     * @return $this
     */
    public function setPrimarySku($primarySku);

    /**
     * Get Status
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Set Status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Get Currency
     *
     * @return string|null
     */
    public function getCurrency();

    /**
     * Set Currency
     *
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency);

    /**
     * Get Locale
     *
     * @return string|null
     */
    public function getLocale();

    /**
     * Set Locale
     *
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale);

    /**
     * Get Sub Total
     *
     * @return float|null
     */
    public function getSubTotal();

    /**
     * Set Sub Total
     *
     * @param float $subTotal
     * @return $this
     */
    public function setSubTotal($subTotal);

    /**
     * Get Total Paid
     *
     * @return float|null
     */
    public function getTotalPaid();

    /**
     * Set Total Paid
     *
     * @param float $totalPaid
     * @return $this
     */
    public function setTotalPaid($totalPaid);

    /**
     * Get Total
     *
     * @return float|null
     */
    public function getTotal();

    /**
     * Set Total
     *
     * @param float $total
     * @return $this
     */
    public function setTotal($total);

    /**
     * Get Shipping Total
     *
     * @return float|null
     */
    public function getShippingTotal();

    /**
     * Set Shipping Total
     *
     * @param float $shippingTotal
     * @return $this
     */
    public function setShippingTotal($shippingTotal);

    /**
     * Get Purchase URL
     *
     * @return string|null
     */
    public function getPurchaseUrl();

    /**
     * Set Purchase URL
     *
     * @param string $purchaseUrl
     * @return $this
     */
    public function setPurchaseUrl($purchaseUrl);

    /**
     * Get Discount Total
     *
     * @return float|null
     */
    public function getDiscountTotal();

    /**
     * Set Discount Total
     *
     * @param float $discountTotal
     * @return $this
     */
    public function setDiscountTotal($discountTotal);

    /**
     * Get Commission Total
     *
     * @return float|null
     */
    public function getCommissionTotal();

    /**
     * Set Commission Total
     *
     * @param float $commissionTotal
     * @return $this
     */
    public function setCommissionTotal($commissionTotal);

    /**
     * Get Discount Codes Applied
     *
     * @return string|null
     */
    public function getDiscountCodesApplied();

    /**
     * Set Discount Codes Applied
     *
     * @param string $discountCodesApplied
     * @return $this
     */
    public function setDiscountCodesApplied($discountCodesApplied);

    /**
     * Get Checkout Domain
     *
     * @return string|null
     */
    public function getCheckoutDomain();

    /**
     * Set Checkout Domain
     *
     * @param string $checkoutDomain
     * @return $this
     */
    public function setCheckoutDomain($checkoutDomain);

    /**
     * Get Ext Store ID
     *
     * @return string|null
     */
    public function getExtStoreId();

    /**
     * Set Ext Store ID
     *
     * @param string $extStoreId
     * @return $this
     */
    public function setExtStoreId($extStoreId);

    /**
     * Get Ext Attributes
     *
     * @return mixed
     */
    public function getExtAttributes();

    /**
     * Set Ext Attributes
     *
     * @param mixed $extAttributes
     * @return $this
     */
    public function setExtAttributes($extAttributes);

    /**
     * Get Payment Method
     *
     * @return string|null
     */
    public function getPaymentMethod();

    /**
     * Set Payment Method
     *
     * @param string $paymentMethod
     * @return $this
     */
    public function setPaymentMethod($paymentMethod);

    /**
     * Get Payment Transaction ID
     *
     * @return string|null
     */
    public function getPaymentTransactionId();

    /**
     * Set Payment Transaction ID
     *
     * @param string $paymentTransactionId
     * @return $this
     */
    public function setPaymentTransactionId($paymentTransactionId);

    /**
     * Get Payment Data
     *
     * @return mixed
     */
    public function getPaymentData();

    /**
     * Set Payment Data
     *
     * @param mixed $paymentData
     * @return $this
     */
    public function setPaymentData($paymentData);

    /**
     * Get Created At
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set Created At
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get Shipping Method
     *
     * @return string|null
     */
    public function getShippingMethod();

    /**
     * Set Shipping Method
     *
     * @param string $shippingMethod
     * @return $this
     */
    public function setShippingMethod($shippingMethod);

    /**
     * Get Shipping Title
     *
     * @return string|null
     */
    public function getShippingTitle();

    /**
     * Set Shipping Title
     *
     * @param string $shippingTitle
     * @return $this
     */
    public function setShippingTitle($shippingTitle);

    /**
     * Get Items
     *
     * @return mixed
     */
    public function getItems();

    /**
     * Set Items
     *
     * @param mixed $items
     * @return $this
     */
    public function setItems($items);

    /**
     * Get Customer
     *
     * @return mixed
     */
    public function getCustomer();

    /**
     * Set Customer
     *
     * @param mixed $customer
     * @return $this
     */
    public function setCustomer($customer);

    /**
     * Get Tax Total
     *
     * @return float|null
     */
    public function getTaxTotal();

    /**
     * Set Tax Total
     *
     * @param float $taxTotal
     * @return $this
     */
    public function setTaxTotal($taxTotal);

    /**
     * Get Tax Total
     *
     * @return float|null
     */
    public function getTotalExTax();

    /**
     * Set Tax Total
     *
     * @param float $totalExTax
     * @return $this
     */
    public function setTotalExTax($totalExTax);
}
