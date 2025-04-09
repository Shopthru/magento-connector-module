<?php
/**
 * ImportOrderManagement.php - Modernized with PHP 8.1 features
 */
namespace Shopthru\Connector\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Shopthru\Connector\Api\Data\ImportLogInterface;
use Shopthru\Connector\Api\Data\OrderImportInterface;
use Shopthru\Connector\Api\ImportOrderManagementInterface;
use Shopthru\Connector\Helper\Logging;
use Shopthru\Connector\Model\Config as ModuleConfig;
use Shopthru\Connector\Model\ImportProcessors\DirectOrderCreator;
use Shopthru\Connector\Model\ImportProcessors\PlaceQuoteOrder;

class ImportOrderManagement implements ImportOrderManagementInterface
{
    /**
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param Logging $loggingHelper
     * @param Config $moduleConfig
     * @param OrderSender $orderSender
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param StockRegistryInterface $stockRegistry
     * @param PlaceQuoteOrder $placeQuoteOrder
     * @param DirectOrderCreator $directOrderCreator
     */
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly Logging $loggingHelper,
        private readonly ModuleConfig $moduleConfig,
        private readonly OrderSender $orderSender,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly StockRegistryInterface $stockRegistry,
        private readonly PlaceQuoteOrder $placeQuoteOrder,
        private readonly DirectOrderCreator $directOrderCreator,
        private readonly ImportOrderContext $importOrderContext
    ) {
    }

    /**
     * @inheritDoc
     */
    public function importOrders(array $orders): array
    {
        $result = [];

        //set context of shopthru import orders
        $this->importOrderContext->setIsShopthruImport(true);

        foreach ($orders as $orderData) {
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
                    $result[] = $logEntry;
                    continue;
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
                    $result[] = $logEntry;
                    continue;
                }

                $order = $this->directOrderCreator->create($orderData, $logEntry);
//                $order = $this->placeQuoteOrder->create($orderData, $logEntry);

                // Send email if enabled
                if ($this->moduleConfig->isTriggerEmailEnabled()) {
                    $this->loggingHelper->addEventLog(
                        $logEntry,
                        EventType::EMAIL_SENDING,
                        'Sending order confirmation email',
                        ['order_id' => $order->getIncrementId()]
                    );

                    $this->orderSender->send($order);

                    $this->loggingHelper->addEventLog(
                        $logEntry,
                        EventType::EMAIL_SENT,
                        'Order confirmation email sent',
                        ['order_id' => $order->getIncrementId()]
                    );
                } else {
                    $this->loggingHelper->addEventLog(
                        $logEntry,
                        EventType::EMAIL_SKIPPED,
                        'Order confirmation email skipped due to configuration'
                    );
                }

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
                    ImportLogInterface::STATUS_SUCCESS,
                    ['order_info' => $this->getOrderSummary($order)],
                    null,
                    $order->getId()
                );

                $result[] = $logEntry;
            } catch (\Exception $e) {
                // Log the error
                $this->loggingHelper->addEventLog(
                    $logEntry,
                    EventType::IMPORT_ERROR,
                    'Error importing order: ' . $e->getMessage(),
                    ['trace' => $e->getTraceAsString()]
                );

                // Log the error and update the log entry
                $this->loggingHelper->logError(
                    'Error importing order: ' . $e->getMessage(),
                    ['trace' => $e->getTraceAsString()]
                );
                $this->loggingHelper->updateImportLog(
                    $logEntry,
                    ImportLogInterface::STATUS_FAILED,
                    null,
                    $e->getMessage()
                );
                $result[] = $logEntry;
            }
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
            ->addFilter('status', ImportLogInterface::STATUS_SUCCESS)
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
        // If allow zero stock is enabled, skip validation
        if ($this->moduleConfig->isAllowZeroStockEnabled()) {
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
                $this->loggingHelper->logError('Product not found: ' . $sku);
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
                $this->loggingHelper->logError('Error checking stock: ' . $e->getMessage());
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
     * Prepare customer data
     *
     * @param OrderImportInterface $orderData
     * @param mixed $store
     * @param ImportLogInterface $logEntry
     * @return int|null Customer ID if customer should be linked, otherwise null
     */
    private function prepareCustomer(
        OrderImportInterface $orderData,
        $store,
        ImportLogInterface $logEntry
    ): ?int {
        $customerData = $orderData->getCustomer();
        $customerEmail = $customerData['email'];
        $customerId = null;

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::CUSTOMER_PREPARING,
            'Preparing customer data',
            ['email' => $customerEmail]
        );

        // If configured to link to existing customer, try to find one with the same email
        if ($this->moduleConfig->isLinkCustomerEnabled()) {
            try {
                $customer = $this->customerRepository->get($customerEmail);
                $customerId = $customer->getId();

                $this->loggingHelper->addEventLog(
                    $logEntry,
                    EventType::CUSTOMER_FOUND,
                    'Found existing customer',
                    ['customer_id' => $customerId]
                );
            } catch (NoSuchEntityException $e) {
                // Customer doesn't exist, we'll continue with a guest order
                $this->loggingHelper->addEventLog(
                    $logEntry,
                    EventType::CUSTOMER_GUEST,
                    'No existing customer found, proceeding with guest checkout'
                );
                $customerId = null;
            } catch (\Exception $e) {
                $this->loggingHelper->addEventLog(
                    $logEntry,
                    EventType::CUSTOMER_ERROR,
                    'Error finding customer: ' . $e->getMessage(),
                    ['email' => $customerEmail]
                );
                $this->loggingHelper->logError('Error finding customer: ' . $e->getMessage());
                $customerId = null;
            }
        } else {
            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::CUSTOMER_GUEST,
                'Using guest checkout (customer linking disabled)'
            );
        }

        return $customerId;
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
