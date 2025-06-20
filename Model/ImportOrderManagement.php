<?php
/**
 * ImportOrderManagement.php - Modernized with PHP 8.1 features
 */
namespace Shopthru\Connector\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Framework\Webapi\Rest\Request as RestRequest;

use Shopthru\Connector\Api\Data\ConfirmOrderRequestInterface;
use Shopthru\Connector\Api\Data\ImportLogInterface;
use Shopthru\Connector\Api\Data\OrderImportInterface;
use Shopthru\Connector\Api\Data\OrderImportResponseInterface;
use Shopthru\Connector\Api\Data\OrderImportResponseInterfaceFactory;
use Shopthru\Connector\Api\ImportOrderManagementInterface;
use Shopthru\Connector\Helper\Logging;
use Shopthru\Connector\Model\Config as ModuleConfig;
use Shopthru\Connector\Model\ImportProcessors\DirectOrderCreator;

class ImportOrderManagement implements ImportOrderManagementInterface
{
    /**
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param Logging $loggingHelper
     * @param Config $moduleConfig
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param StockRegistryInterface $stockRegistry
     * @param DirectOrderCreator $directOrderCreator
     * @param ImportOrderContext $importOrderContext
     * @param OrderImportResponseInterfaceFactory $orderImportResponseFactory
     * @param RestRequest $restRequest
     */
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly Logging $loggingHelper,
        private readonly ModuleConfig $moduleConfig,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly StockRegistryInterface $stockRegistry,
        private readonly DirectOrderCreator $directOrderCreator,
        private readonly ImportOrderContext $importOrderContext,
        private readonly OrderImportResponseInterfaceFactory $orderImportResponseFactory,
        private readonly RestRequest $restRequest,
    ) {
    }

    public function cancelOrder($shopthruOrderId, $orderData): OrderImportResponseInterface
    {
        $this->importOrderContext->setIsShopthruImport(true);
        $logEntry = $this->loggingHelper->getLogByShopthruOrderId($shopthruOrderId);

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::ORDER_CANCELLATION_STARTED,
            'Started order cancellation process'
        );

        $params = $this->restRequest->getParams();
        $ignoreStatus = $params['force'] ?? false;

        if (!$ignoreStatus && $logEntry->getStatus() != ImportLogInterface::STATUS_PENDING_PAYMENT) {
            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::ORDER_CANCELLATION_ERROR,
                'Order cannot be cancelled as it is not in pending payment status',
                [
                    'status' => $logEntry->getStatus(),
                    'magento_order_id' => $logEntry->getMagentoOrderId()
                ]
            );
            throw new InputException(__('Order cannot be confirmed as it is not in pending payment status', [
                'status' => $logEntry->getStatus(),
                'magento_order_id' => $logEntry->getMagentoOrderId()
            ]));
        }

        $this->directOrderCreator->cancelOrder($shopthruOrderId, $orderData, $logEntry);
        $this->loggingHelper->updateImportLog(
            $logEntry,
            ImportLogInterface::STATUS_CANCELLED,
            [],
            null,
        );

        $success = $logEntry->getStatus() === ImportLogInterface::STATUS_CANCELLED;

        return $this->buildImportResponse(
            OrderImportResponseInterface::IMPORT_ACTION_CANCEL,
            $success,
            $logEntry
        );
    }

    public function completeOrder($shopthruOrderId, ConfirmOrderRequestInterface $confirmOrderData): OrderImportResponseInterface
    {
        $this->importOrderContext->setIsShopthruImport(true);
        $logEntry = $this->loggingHelper->getLogByShopthruOrderId($shopthruOrderId);

        $params = $this->restRequest->getParams();
        $ignoreStatus = $params['force'] ?? false;

        if (!$ignoreStatus && $logEntry->getStatus() != ImportLogInterface::STATUS_PENDING_PAYMENT) {
            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::ORDER_COMPLETION_ERROR,
                'Order cannot be completed as it is not in pending payment status',
                [
                    'status' => $logEntry->getStatus(),
                    'magento_order_id' => $logEntry->getMagentoOrderId()
                ]
            );
            throw new InputException(__('Order cannot be confirmed as it is not in pending payment status', [
                'status' => $logEntry->getStatus(),
                'magento_order_id' => $logEntry->getMagentoOrderId()
            ]));
        }

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::ORDER_COMPLETION_STARTED,
            'Started order completion process'
        );

        $this->directOrderCreator->confirmOrder($shopthruOrderId, $confirmOrderData, $logEntry);
        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::ORDER_COMPLETION_COMPLETED,
            'Order completion process completed'
        );
        $this->loggingHelper->updateImportLog(
            $logEntry,
            ImportLogInterface::STATUS_SUCCESS,
            [],
            null,
        );

        $success = $logEntry->getStatus() === ImportLogInterface::STATUS_SUCCESS;

        return $this->buildImportResponse(
            OrderImportResponseInterface::IMPORT_ACTION_CONFIRM,
            $success,
            $logEntry
        );
    }

    public function importOrderProcess(OrderImportInterface $orderData)
    {
        $shopthruOrderId = $orderData->getOrderId();
        $publisher = $orderData->getPublisher();
        $publisherRef = isset($publisher['ref']) ? $publisher['ref'] : null;
        $publisherName = isset($publisher['name']) ? $publisher['name'] : null;

        // Create initial log entry
        $logEntry = $this->loggingHelper->createImportLog(
            $shopthruOrderId,
            $publisherRef,
            $publisherName,
            ImportLogInterface::STATUS_PENDING,
            $orderData->getData(), // Store original Shopthru data
        );

        // Log initial event
        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::IMPORT_STARTED,
            'Started processing Shopthru order: ' . $shopthruOrderId
        );

        try {
            // Check if order with this Shopthru ID already exists
            $existingOrderId = $this->checkExistingOrder($shopthruOrderId);
            if ($existingOrderId) {
                $this->loggingHelper->addEventLog(
                    $logEntry,
                    EventType::IMPORT_DUPLICATE,
                    'Order already exists in Magento',
                    ['magento_order_id' => $existingOrderId]
                );

                $this->loggingHelper->updateImportLog(
                    $logEntry,
                    ImportLogInterface::STATUS_FAILED,
                    null,
                    "Order with Shopthru ID {$shopthruOrderId} already exists in Magento (Order #{$existingOrderId})"
                );
                throw new InputException(__("Order with Shopthru ID {$shopthruOrderId} already exists in Magento (Order #{$existingOrderId})"));
            }

            // Process the order
            $storeId = $orderData->getExtStoreId() ? (int)$orderData->getExtStoreId() : null;
            $store = $this->loggingHelper->getStore($storeId);

            // Check stock levels if needed
            if (!$this->validateStock($orderData, $logEntry)) {
                $this->loggingHelper->addEventLog(
                    $logEntry,
                    EventType::STOCK_INSUFFICIENT,
                    'Insufficient stock for one or more products'
                );

                $this->loggingHelper->updateImportLog(
                    $logEntry,
                    ImportLogInterface::STATUS_FAILED,
                    null,
                    "Insufficient stock for one or more products in the order"
                );
            }

            $order = $this->directOrderCreator->create($orderData, $logEntry);
            $logEntry->setMagentoOrder($order);

            // Log completion event
            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::IMPORT_COMPLETED,
                'Order import completed successfully',
                ['order_id' => $order->getIncrementId()]
            );

            // Update log status to success
            $this->loggingHelper->updateImportLog(
                $logEntry,
                ImportLogInterface::STATUS_PENDING_PAYMENT,
                ['order_info' => $this->getOrderSummary($order)],
                null,
                $order->getId()
            );

            $result[] = $logEntry;
        } catch (InputException $e) {
            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::IMPORT_ERROR,
                'Error importing order: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]
            );
            throw $e;
        } catch (\Exception $e) {
            // Log the error
            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::IMPORT_ERROR,
                'Error importing order: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]
            );

            // Log the error and update the log entry
            $this->loggingHelper->updateImportLog(
                $logEntry,
                ImportLogInterface::STATUS_FAILED,
                null,
                $e->getMessage()
            );
            return $logEntry;
        }

        return $logEntry;
    }

    /**
     * @inheritDoc
     */
    public function importOrder(OrderImportInterface $orderData): OrderImportResponseInterface
    {
        $this->importOrderContext->setIsShopthruImport(true);
        $logEntry = $this->importOrderProcess($orderData);

        $success = $logEntry->getStatus() === ImportLogInterface::STATUS_PENDING_PAYMENT;
        return $this->buildImportResponse(
            OrderImportResponseInterface::IMPORT_ACTION_CREATE,
            $success,
            $logEntry
        );
    }

    private function buildImportResponse(string $action, bool $success, ImportLogInterface $logEntry, ?OrderInterface $order = null): OrderImportResponseInterface
    {
        if (!$order && $logEntry->getMagentoOrder(false)) {
            $order = $logEntry->getMagentoOrder(false);
        }

        $orderResponse = $this->orderImportResponseFactory->create();
        $orderResponse->setImportAction($action);
        $orderResponse->setImportActionSuccess($success);
        $orderResponse->setShopthruOrderId($logEntry->getShopthruOrderId());
        if ($logEntry->getMagentoOrderId()) {
            $orderResponse->setMagentoOrderId($logEntry->getMagentoOrderId());
        }
        $orderResponse->setImportStatus($logEntry->getStatus());
        $orderResponse->setImportLogId($logEntry->getImportId());
        if ($logEntry->getFailedReason()) {
            $orderResponse->setErrorMessage($logEntry->getFailedReason());
        }
        if ($order) {
            $orderResponse->setMagentoOrderStatus($order->getStatus());
        }

        return $orderResponse;
    }

    /**
     * @inheritDoc
     */
    public function importMultipleOrders(array $orders): array
    {
        $result = [];

        //set context of shopthru import orders
        $this->importOrderContext->setIsShopthruImport(true);

        foreach ($orders as $orderData) {
            $this->importOrderProcess($orderData);
        }

        return $result;
    }

    /**
     * Check if order with the given Shopthru order ID already exists
     *
     * @param string $shopthruOrderId
     * @return string|null Magento order ID if exists, null otherwise
     */
    private function checkExistingOrder(string $shopthruOrderId): ?string
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('shopthru_order_id', $shopthruOrderId)
            ->create();

        $logEntries = $this->loggingHelper->getImportLogRepository()->getList($searchCriteria);

        if (!empty($logEntries)) {
            foreach ($logEntries as $logEntry) {
                if ($logEntry->getMagentoOrderId()) {
                    return $logEntry->getMagentoOrderId();
                }
            }
        }

        return null;
    }

    /**
     * Validate stock levels for all items in the order
     *
     * @param OrderImportInterface $orderData
     * @param ImportLogInterface $logEntry
     * @return bool
     */
    private function validateStock(OrderImportInterface $orderData, ImportLogInterface $logEntry): bool
    {
        // If validate stock is enabled, skip validation
        if (!$this->moduleConfig->isValidateStockEnabled()) {
            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::STOCK_VALIDATION_SKIPPED,
                'Stock validation skipped due to configuration'
            );
            return true;
        }

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::STOCK_VALIDATION_STARTED,
            'Started validating stock for order items'
        );

        $items = $orderData->getItems();
        $allItemsInStock = true;
        $outOfStockItems = [];

        foreach ($items as $item) {
            $sku = $item['product_sku'];
            $qty = $item['quantity'];

            try {
                $product = $this->productRepository->get($sku);
                $stockItem = $this->stockRegistry->getStockItem($product->getId());

                if (!$stockItem->getIsInStock() || $stockItem->getQty() < $qty) {
                    $allItemsInStock = false;
                    $outOfStockItems[] = [
                        'sku' => $sku,
                        'requested_qty' => $qty,
                        'available_qty' => $stockItem->getQty(),
                        'is_in_stock' => $stockItem->getIsInStock()
                    ];
                }
            } catch (NoSuchEntityException $e) {
                $this->loggingHelper->addEventLog(
                    $logEntry,
                    EventType::STOCK_VALIDATION_ERROR,
                    'Product not found: ' . $sku,
                    ['error' => $e->getMessage()]
                );
                $allItemsInStock = false;
                $outOfStockItems[] = [
                    'sku' => $sku,
                    'error' => 'Product not found'
                ];
                continue;
            } catch (\Exception $e) {
                $this->loggingHelper->addEventLog(
                    $logEntry,
                    EventType::STOCK_VALIDATION_ERROR,
                    'Error checking stock: ' . $e->getMessage(),
                    ['sku' => $sku]
                );
                $allItemsInStock = false;
                $outOfStockItems[] = [
                    'sku' => $sku,
                    'error' => $e->getMessage()
                ];
                continue;
            }
        }

        if ($allItemsInStock) {
            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::STOCK_VALIDATION_SUCCESS,
                'All items are in stock'
            );
        } else {
            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::STOCK_VALIDATION_FAILED,
                'Some items are out of stock',
                ['out_of_stock_items' => $outOfStockItems]
            );
        }

        return $allItemsInStock;
    }

    /**
     * Get order summary information
     *
     * @param OrderInterface $order
     * @return array
     */
    private function getOrderSummary(OrderInterface $order): array
    {
        $items = [];
        foreach ($order->getAllItems() as $item) {
            $items[] = [
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'qty' => $item->getQtyOrdered(),
                'price' => $item->getPrice(),
                'row_total' => $item->getRowTotal()
            ];
        }

        return [
            'increment_id' => $order->getIncrementId(),
            'status' => $order->getStatus(),
            'customer_email' => $order->getCustomerEmail(),
            'created_at' => $order->getCreatedAt(),
            'grand_total' => $order->getGrandTotal(),
            'subtotal' => $order->getSubtotal(),
            'shipping_amount' => $order->getShippingAmount(),
            'discount_amount' => $order->getDiscountAmount(),
            'item_count' => count($items),
            'items' => $items
        ];
    }
}
