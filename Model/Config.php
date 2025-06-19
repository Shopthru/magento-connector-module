<?php
/**
 * Config.php
 */
namespace Shopthru\Connector\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Shopthru\Connector\Model\Config\Source\CancelledOrderAction;

class Config
{
    /**
     * Config paths
     */
    private const XML_PATH_TRIGGER_EMAIL = 'shopthru/general/trigger_email';
    private const XML_PATH_DECREMENT_STOCK = 'shopthru/general/decrement_stock';
    private const XML_PATH_VALIDATE_AVAILABLE_STOCK = 'shopthru/general/validate_available_stock';
    private const XML_PATH_LINK_CUSTOMER = 'shopthru/general/link_customer';
    private const XML_PATH_AUTO_INVOICE = 'shopthru/general/auto_invoice';
    private const XML_PATH_ORDER_STATUS = 'shopthru/general/order_status';
    private const XML_PATH_PENDING_ORDER_STATUS = 'shopthru/general/pending_order_status';
    private const XML_PATH_CANCELLED_ORDER_ACTION = 'shopthru/general/cancelled_order_action';
    private const XML_PATH_CANCELLED_ORDER_STATUS = 'shopthru/general/cancelled_order_status';
    private const XML_PATH_ADMIN_API_INTERCEPT_ENABLED = 'shopthru/general/admin_api_intercept_enabled';

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
    public function isValidateStockEnabled(?int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_VALIDATE_AVAILABLE_STOCK,
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
        ) ?? false;
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
        ) ?? true;
    }

    public function getPendingOrderStatus(?int $storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PENDING_ORDER_STATUS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?? Order::STATE_PENDING_PAYMENT;
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
        ) ?? Order::STATE_PROCESSING;
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

    public function getCancelledOrderAction(?int $storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CANCELLED_ORDER_ACTION,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?? CancelledOrderAction::UPDATE_STATUS;
    }

    public function getCancelledOrderStatus(?int $storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CANCELLED_ORDER_STATUS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?? Order::STATE_CANCELED;
    }

    public function adminApiInterceptEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ADMIN_API_INTERCEPT_ENABLED
        );
    }
}
