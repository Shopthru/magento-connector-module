<?php
/**
 * Config.php
 */
namespace Shopthru\Connector\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    /**
     * Config paths
     */
    private const XML_PATH_TRIGGER_EMAIL = 'shopthru/general/trigger_email';
    private const XML_PATH_DECREMENT_STOCK = 'shopthru/general/decrement_stock';
    private const XML_PATH_ALLOW_ZERO_STOCK = 'shopthru/general/allow_zero_stock';
    private const XML_PATH_LINK_CUSTOMER = 'shopthru/general/link_customer';
    private const XML_PATH_AUTO_INVOICE = 'shopthru/general/auto_invoice';
    private const XML_PATH_ORDER_STATUS = 'shopthru/general/order_status';

    private const XML_PATH_TEST_ORDER_SKU = 'shopthru/general/test_order_sku';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Check if trigger email is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isTriggerEmailEnabled(?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_TRIGGER_EMAIL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if decrement stock is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isDecrementStockEnabled(?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_DECREMENT_STOCK,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if allow zero stock is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isAllowZeroStockEnabled(?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ALLOW_ZERO_STOCK,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if link customer is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isLinkCustomerEnabled(?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_LINK_CUSTOMER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if auto invoice is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isAutoInvoiceEnabled(?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_AUTO_INVOICE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get order status for new orders
     *
     * @param int|null $storeId
     * @return string|null
     */
    public function getOrderStatus(?int $storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ORDER_STATUS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return mixed
     */
    public function getTestOrderSku(?int $storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_TEST_ORDER_SKU,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
