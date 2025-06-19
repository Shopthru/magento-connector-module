<?php

namespace Shopthru\Connector\Plugin\Rest;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Customer\Model\Group;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\Webapi\Rest\Request as RestRequest;
use Magento\Catalog\Api\ProductRepositoryInterface;

use Shopthru\Connector\Api\Data\ImportLogInterface;
use Shopthru\Connector\Helper\Logging as LoggingHelper;
use Shopthru\Connector\Helper\OrderProcesses;
use Shopthru\Connector\Model\Config as ModuleConfig;
use Psr\Log\LoggerInterface;
use Shopthru\Connector\Model\EventType;

class OrderRepositoryPlugin
{
    const SHOPTHRU_URL_PARAM = 'shopthru_order';

    const SHOPTHRU_FLAG_PARAM_NAME = 'st_flags';

    const SHOPTHRU_FLAG_PARAM_VALIDATE_STOCK = 'validate_stock';
    const SHOPTHRU_FLAG_PARAM_DECREMENT_STOCK = 'decrement_stock';
    const SHOPTHRU_FLAG_PARAM_TRIGGER_EMAIL = 'trigger_email';
    const SHOPTHRU_FLAG_PARAM_AUTO_INVOICE = 'auto_invoice';
    const SHOPTHRU_FLAG_PARAM_LINK_CUSTOMER = 'link_customer';


    /**
     * Flag to prevent infinite recursion
     *
     * @var bool
     */
    private static bool $isProcessing = false;

    private array $processingFlags = [];

    private ImportLogInterface $importLog;

    public function __construct(
        private readonly RestRequest $restRequest,
        private readonly LoggerInterface $logger,
        private readonly ModuleConfig $config,
        private readonly OrderProcesses $orderProcesses,
        private readonly StockRegistryInterface $stockRegistry,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggingHelper $loggingHelper,
    ) {}

    private function isProcessing(): bool
    {
        return self::$isProcessing;
    }

    private function setIsProcessing(bool $flag = true)
    {
        self::$isProcessing = $flag;
    }

    private function getProcessingFlag(string $key, bool $default=false): bool
    {
        return (bool) $this->processingFlags[$key] ?? false;
    }

    private function hasProcessingFlag(string $key): bool
    {
        return array_key_exists($key, $this->processingFlags);
    }

    private function shouldValidateStock(): bool
    {
        if ($this->hasProcessingFlag(self::SHOPTHRU_FLAG_PARAM_VALIDATE_STOCK)) {
            return $this->getProcessingFlag(self::SHOPTHRU_FLAG_PARAM_VALIDATE_STOCK);
        }
        return $this->config->isValidateStockEnabled();
    }

    private function shouldDecrementStock()
    {
        if ($this->hasProcessingFlag(self::SHOPTHRU_FLAG_PARAM_DECREMENT_STOCK)) {
            return $this->getProcessingFlag(self::SHOPTHRU_FLAG_PARAM_DECREMENT_STOCK);
        }
        return $this->config->isDecrementStockEnabled();
    }

    private function shouldTriggerEmail()
    {
        if ($this->hasProcessingFlag(self::SHOPTHRU_FLAG_PARAM_TRIGGER_EMAIL)) {
            return $this->getProcessingFlag(self::SHOPTHRU_FLAG_PARAM_TRIGGER_EMAIL);
        }
        return $this->config->isTriggerEmailEnabled();
    }

    private function shouldAutoInvoice()
    {
        if ($this->hasProcessingFlag(self::SHOPTHRU_FLAG_PARAM_AUTO_INVOICE)) {
            return $this->getProcessingFlag(self::SHOPTHRU_FLAG_PARAM_AUTO_INVOICE);
        }
        return $this->config->isAutoInvoiceEnabled();
    }

    private function shouldLinkCustomer()
    {
        if ($this->hasProcessingFlag(self::SHOPTHRU_FLAG_PARAM_LINK_CUSTOMER)) {
            return $this->getProcessingFlag(self::SHOPTHRU_FLAG_PARAM_LINK_CUSTOMER);
        }
        return $this->config->isLinkCustomerEnabled();
    }

    public function beforeSave(
        OrderRepositoryInterface $subject,
        OrderInterface $order
    ) {
        if ($this->isProcessing()) {
            return [$order];
        }

        if (!$this->shouldIntercept($order)) {
            return [$order];
        }

        $this->importLog = $this->loggingHelper->createImportLog($order->getExtOrderId());

        $this->setProcessingFlags($this->getRestParams(self::SHOPTHRU_FLAG_PARAM_NAME, []));
        $this->loggingHelper->addEventLog(
            $this->importLog,
            EventType::IMPORT_STARTED,
            'Started processing Shopthru order: ' . $order->getExtOrderId(),
            ['processing_flags' => $this->processingFlags]
        );

        $this->setIsProcessing(true);
        $this->fillMissingData($order);
        $this->runPreSaveActions($order);
        $this->setIsProcessing(false);
        return [$order];
    }

    private function setProcessingFlags(array $params)
    {
        $this->processingFlags = $params;
    }

    public function afterSave(
        OrderRepositoryInterface $subject,
        OrderInterface $result
    ) {
        if ($this->isProcessing()) {
            return $result;
        }

        if (!$this->shouldIntercept($result)) {
            return $result;
        }

        $this->setIsProcessing(true);
        $this->runPostCreate($result);
        $this->setIsProcessing(false);

        return $result;
    }

    private function getItemsStock(OrderInterface $order): array
    {
        $items = $order->getAllItems();
        $allItemsInStock = true;
        $outOfStockItems = [];

        foreach ($items as $item) {
            $sku = $item->getSku();
            $qty = $item->getQtyOrdered();

            try {
                $productId = $item->getProductId();
                if ($productId) {
                    //first check we can find a product if we have a product id set
                    $product = $this->productRepository->getById($productId);
                } else {
                    //otherwise try to find a product by sku
                    $product = $this->productRepository->get($sku);
                    $productId = $product->getId();
                    $item->setProductId($product->getId());
                }

                $product = $item->getProduct();
                if (!$product) {
                    throw new NoSuchEntityException(__('Product with SKU "%1" not found', $sku));
                }
                $stockItem = $this->stockRegistry->getStockItem($productId);
                if (!$stockItem->getProductId()) {
                    throw new NoSuchEntityException(__('Product with SKU "%1" not found', $sku));
                }

                if (!$stockItem->getIsInStock() || $stockItem->getQty() < $qty) {
                    $allItemsInStock = false;
                    $outOfStockItems[] = [
                        'sku' => $sku,
                        'requested_qty' => $qty,
                        'available_qty' => $stockItem->getQty(),
                        'is_in_stock' => $stockItem->getIsInStock(),
                        'error' => 'Insufficient stock'
                    ];
                }
            } catch (NoSuchEntityException $e) {
                $allItemsInStock = false;
                $outOfStockItems[] = [
                    'sku' => $sku,
                    'error' => 'Product not found'
                ];
                continue;
            } catch (\Exception $e) {
                $allItemsInStock = false;
                $outOfStockItems[] = [
                    'sku' => $sku,
                    'error' => $e->getMessage()
                ];
                continue;
            }

        }

        return [$allItemsInStock, $outOfStockItems];
    }

    private function fillMissingData(OrderInterface $order): void
    {
        if ($order->getCustomerGroupId() == null) {
            $order->setCustomerIsGuest(1);
            $order->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
        }

        foreach ($order->getAllItems() as $item) {
            $item->setProductOptions([
                'info_buyRequest' => [
                    'qty' => $item->getQtyOrdered(),
                    'product' => $item->getProductId(),
                    'item' => $item->getProductId()
                ]
            ]);
        }
    }

    private function runPreSaveActions(OrderInterface $order)
    {
        if ($this->shouldLinkCustomer()) {
            $this->loggingHelper->addEventLog(
                $this->importLog,
                EventType::CUSTOMER_PREPARING,
                'Linking customer to order'
            );
            $customerEmail = $order->getCustomerEmail();
            $this->orderProcesses->linkCustomerToOrderIfExists($order, $customerEmail);
            if ($order->getCustomerId()) {
                $this->loggingHelper->addEventLog(
                    $this->importLog,
                    EventType::CUSTOMER_FOUND,
                    'Linked order to existing customer',
                    ['customer_id' => $order->getCustomerId()]
                );
            } else {
                $this->loggingHelper->addEventLog(
                    $this->importLog,
                    EventType::CUSTOMER_GUEST,
                    'No customer found, using guest checkout'
                );
            }
        } else {
            $this->loggingHelper->addEventLog(
                $this->importLog,
                EventType::CUSTOMER_GUEST,
                'Using guest checkout (customer linking disabled)'
            );
        }

        if ($this->shouldValidateStock()) {
            $this->loggingHelper->addEventLog(
                $this->importLog,
                EventType::STOCK_VALIDATION_STARTED,
                'Validating stock for order items'
            );
            [$allItemsInStock, $outOfStockItems] = $this->getItemsStock($order);
            if (!$allItemsInStock) {
                //get the product sku and error into an array
                $productErrors = [];
                foreach ($outOfStockItems as $item) {
                    $productErrors[$item['sku']] = $item['error'] ?? 'Unknown error';
                }
                $this->loggingHelper->addEventLog(
                    $this->importLog,
                    EventType::STOCK_INSUFFICIENT,
                    'Insufficient stock for one or more products',
                    ['out_of_stock_items' => $outOfStockItems]
                );

                throw new LocalizedException(__("Insufficient stock for one or more products", $productErrors));
            } else {
                $this->loggingHelper->addEventLog(
                    $this->importLog,
                    EventType::STOCK_VALIDATION_SUCCESS,
                    'All items are in stock'
                );
            }
        }
    }

    private function runPostCreate(OrderInterface $order)
    {
        $this->loggingHelper->updateImportLog(
            $this->importLog,
            ImportLogInterface::STATUS_SUCCESS,
            [],
            null,
            $order->getId()
        );
        $comments = [];
        try {
            if ($this->shouldDecrementStock()) {
                $this->loggingHelper->addEventLog(
                    $this->importLog,
                    EventType::STOCK_DECREMENTING,
                    'Decrementing stock for order items'
                );
                $this->orderProcesses->decrementStock($order);
                $this->loggingHelper->addEventLog(
                    $this->importLog,
                    EventType::STOCK_DECREMENTED,
                    'Stock decremented for order items'
                );
                $comments[] = "Stock decremented for order items";
            } else {
                $this->loggingHelper->addEventLog(
                    $this->importLog,
                    EventType::STOCK_DECREMENTING,
                    'Stock decrementing skipped (disabled in configuration)'
                );
            }
        } catch (\Exception $e) {
            $comments[] ='Failed to decrement stock';
            $this->loggingHelper->addEventLog(
                $this->importLog,
                EventType::STOCK_DECREMENT_ERROR,
                'Error decrementing stock: ' . $e->getMessage()
            );
        }

        try {
            if ($this->shouldAutoInvoice()) {
                $this->loggingHelper->addEventLog(
                  $this->importLog,
                  EventType::INVOICE_CREATING,
                  'Creating invoice'
                );
                $this->orderProcesses->createInvoice($order);
                $comments[] = "Invoice created and marked as paid";
            }
        } catch (\Exception $e) {
            $comments[] ='Failed to create invoice';
            $this->loggingHelper->addEventLog(
                $this->importLog,
                EventType::INVOICE_ERROR,
                'Error creating invoice: ' . $e->getMessage()
            );
        }

        try {
            if ($this->shouldTriggerEmail()) {
                $this->loggingHelper->addEventLog(
                    $this->importLog,
                    EventType::EMAIL_SENDING,
                    'Sending order confirmation email'
                );
                $this->orderProcesses->sendOrderConfirmationEmail($order);
                $comments[] = "Order confirmation email sent";
            }
        } catch (\Exception $e) {
            $comments[] ='Failed to send order confirmation email';
            $this->loggingHelper->addEventLog(
                $this->importLog,
                EventType::EMAIL_ERROR,
                'Error sending order confirmation email: ' . $e->getMessage()
            );
        }


        if ($comments) {
            try {
                $this->orderProcesses->addMultipleCommentsToOrder($order, $comments);
            } catch (\Exception $e) {
                $this->logger->error('Shopthru Connector: Error adding comments to order: ' . $e->getMessage());
            }

        }

        return $order;
    }

    private function shouldIntercept(OrderInterface $order): bool
    {
        return (
            $this->config->adminApiInterceptEnabled() &&
            $this->isOrderCreateEndpoint() &&
            $this->getRestParams(self::SHOPTHRU_URL_PARAM) &&
            $order->getExtOrderId() !== null
        );
    }

    private function isOrderCreateEndpoint(): bool
    {
        $requestPath = $this->restRequest->getRequestUri();

        // Check if the request path contains V1/orders/create
        return (strpos($requestPath, 'V1/orders/create') !== false);
    }

    /**
     * Get REST API request parameters
     *
     * @return mixed
     */
    private function getRestParams($key=null, $default=null): mixed
    {
        try {
            // Get the request body for REST API calls
            $params = $this->restRequest->getParams();

            if ($key !== null) {
                return $params[$key] ?? $default;
            }

            return is_array($params) ? $params : [];
        } catch (\Exception $e) {
            $this->logger->error('Shopthru Connector: Error getting REST parameters: ' . $e->getMessage());
            return [];
        }
    }

    private function getRestBody(): array
    {
        try {
            $body = $this->restRequest->getBodyParams();
            return is_array($body) ? $body : [];
        } catch (\Exception $e) {
            $this->logger->error('Shopthru Connector: Error getting REST body: ' . $e->getMessage());
            return [];
        }

    }


}
