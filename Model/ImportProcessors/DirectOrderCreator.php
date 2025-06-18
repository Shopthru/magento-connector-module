<?php
/**
 * DirectOrderCreator.php
 */
namespace Shopthru\Connector\Model\ImportProcessors;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Group;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Api\Data\OrderAddressInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\OrderPaymentInterfaceFactory;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Api\Data\OrderItemInterfaceFactory;
use Magento\Framework\DB\TransactionFactory;

use Shopthru\Connector\Api\Data\ConfirmOrderRequestInterface;
use Shopthru\Connector\Api\Data\ImportLogInterface;
use Shopthru\Connector\Api\Data\OrderImportInterface;
use Shopthru\Connector\Helper\Logging as LoggingHelper;
use Shopthru\Connector\Helper\OrderProcesses;
use Shopthru\Connector\Model\Config as ModuleConfig;
use Shopthru\Connector\Model\EventType;
use Shopthru\Connector\Model\ImportProcessors\DirectOrderCreator\DefaultData;
use \Shopthru\Connector\Model\Payment\Method\Shopthru as ShopthruPayment;

class DirectOrderCreator
{

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggingHelper $loggingHelper
     * @param OrderProcesses $orderProcessesHelper
     * @param ModuleConfig $moduleConfig
     * @param OrderAddressInterfaceFactory $orderAddressFactory
     * @param OrderPaymentInterfaceFactory $orderPaymentFactory
     * @param OrderInterfaceFactory $orderFactory
     * @param OrderItemInterfaceFactory $orderItemFactory
     * @param GroupRepositoryInterface $groupRepository
     * @param TransactionFactory $transactionFactory
     * @param DefaultData $defaultData
     */
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly LoggingHelper $loggingHelper,
        private readonly OrderProcesses $orderProcessesHelper,
        private readonly ModuleConfig $moduleConfig,
        private readonly OrderAddressInterfaceFactory $orderAddressFactory,
        private readonly OrderPaymentInterfaceFactory $orderPaymentFactory,
        private readonly OrderInterfaceFactory $orderFactory,
        private readonly OrderItemInterfaceFactory $orderItemFactory,
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly TransactionFactory $transactionFactory,
        private readonly DefaultData $defaultData
    ) {
    }

    private function initializeOrder(OrderImportInterface $orderData): OrderInterface
    {
        // Get store
        $storeId = $orderData->getExtStoreId() ? (int)$orderData->getExtStoreId() : null;
        $store = $this->loggingHelper->getStore($storeId);

        // Create new order object
        /** @var Order $order */
        $order = $this->orderFactory->create();
        $order->setStoreId($store->getId());

        $this->applyDefaultOrderData($order, $orderData);

        // Set basic order information
        $order->setState(Order::STATE_PENDING_PAYMENT);
        $order->setStatus('pending_payment');

        $order->setIsVirtual((bool) $orderData->getIsVirtual() ?: 0);

        // Set currency code
        $order->setOrderCurrencyCode($orderData->getCurrency() ?: 'GBP');
        $order->setBaseCurrencyCode($orderData->getCurrency() ?: 'GBP');
        $order->setGlobalCurrencyCode($orderData->getCurrency() ?: 'GBP');
        $order->setStoreCurrencyCode($orderData->getCurrency() ?: 'GBP');

        return $order;
    }

    private function setCustomerInformation(
        OrderInterface $order,
        OrderImportInterface $orderData,
        ImportLogInterface $logEntry): void
    {
        // Set customer information
        $customerData = $orderData->getCustomer();

        $order->setCustomerEmail($customerData['email']);
        $order->setCustomerFirstname($this->getFirstName($customerData['name']));
        $order->setCustomerLastname($this->getLastName($customerData['name']));
        $order->setCustomerIsGuest(true)
            ->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);

        // If you want to link to a customer
        if ($this->moduleConfig->isLinkCustomerEnabled()) {
            try {
                $customer = $this->customerRepository->get($customerData['email']);
                $order->setCustomerId($customer->getId());
                $order->setCustomerIsGuest(0);
                $order->setCustomerGroupId($customer->getGroupId());

                $this->loggingHelper->addEventLog(
                    $logEntry,
                    EventType::CUSTOMER_FOUND,
                    'Linked order to existing customer',
                    ['customer_id' => $customer->getId()]
                );
            } catch (\Exception $e) {
                $this->loggingHelper->addEventLog(
                    $logEntry,
                    EventType::CUSTOMER_GUEST,
                    'No customer found, using guest checkout'
                );
            }
        }
    }

    private function setShippingInformation(OrderInterface $order, OrderImportInterface $orderData): void
    {
        // Set shipping method
        $shippingMethodCode = $orderData->getShippingMethod() ?: 'flatrate_flatrate';
        $shippingMethodTitle = $orderData->getShippingTitle() ?: 'Flat Rate Shipping';
        $shippingAmount = (float)$orderData->getShippingTotal();

        $order->setShippingMethod($shippingMethodCode);
        $order->setShippingDescription($shippingMethodTitle);
        $order->setShippingAmount($shippingAmount);
        $order->setBaseShippingAmount($shippingAmount);
        $order->setShippingInclTax($shippingAmount);
        $order->setBaseShippingInclTax($shippingAmount);
    }

    private function setOrderTotals(OrderInterface $order, OrderImportInterface $orderData)
    {
        // Set order totals
        $subTotal = (float)$orderData->getSubTotal();
        $discountAmount = (float)$orderData->getDiscountTotal();
        $grandTotal = (float)$orderData->getTotalPaid();

        $order->setSubtotal($subTotal);
        $order->setSubtotalInclTax($subTotal);
        $order->setBaseSubtotal($subTotal);
        $order->setBaseSubtotalInclTax($subTotal);
        $order->setTaxAmount($orderData->getTaxTotal());
        $order->setTaxInvoiced($orderData->getTaxTotal());

        if ($discountAmount > 0) {
            $order->setDiscountAmount(-$discountAmount);
            $order->setBaseDiscountAmount(-$discountAmount);

            // Store discount code information
            if ($orderData->getDiscountCodesApplied()) {
                $order->setDiscountDescription('Shopthru discount');
                $order->setCouponCode($orderData->getDiscountCodesApplied());
                $order->setData('shopthru_discount_code', $orderData->getDiscountCodesApplied());
            }
        }

        $order->setGrandTotal($grandTotal);
        $order->setBaseGrandTotal($grandTotal);
        $order->setTotalPaid(0); // Set to 0 until order is confirmed
        $order->setBaseTotalPaid(0); // Set to 0 until order is confirmed
    }

    public function confirmOrder($shopthruOrderId, ConfirmOrderRequestInterface $confirmOrderData, ImportLogInterface $importLog = null)
    {
        if (!$importLog) {
            $importLog = $this->loggingHelper->getLogByShopthruOrderId($shopthruOrderId);
        }
        $magentoOrderId = $importLog->getMagentoOrderId();
        $order = $this->orderRepository->get($magentoOrderId);

        // Create invoice if auto-invoice is enabled
        if ($this->moduleConfig->isAutoInvoiceEnabled()) {
            $this->createInvoice($order, $importLog);
        } else {
            $order->setState(Order::STATE_PROCESSING);
            $order->setStatus($this->moduleConfig->getOrderStatus() ?: Order::STATE_PROCESSING);
            $order->setIsInProcess(true);
            $order->setTotalPaid($order->getGrandTotal());
            $order->setBaseTotalPaid($order->getBaseGrandTotal());
            $order->save();
        }

        $payment = $order->getPayment();
        $transactionId = $confirmOrderData->getTransactionId() ?? $confirmOrderData->getPaymentData()['transaction_id'] ?? '';
        $payment->setTransactionId($transactionId);
        $payment->setLastTransId($transactionId);
        $payment->setTransactionAdditionalInfo('transaction_id', $transactionId);
        $payment->save();

        $this->sendOrderEmailifEnabled($order, $importLog);

        // Decrement stock if enabled
        if ($this->moduleConfig->isDecrementStockEnabled()) {
            $this->decrementStock($order, $importLog);
        }

        return $order;
    }

    public function cancelOrder($shopthruOrderId, $orderData, ImportLogInterface $importLog = null)
    {
        if (!$importLog) {
            $importLog = $this->loggingHelper->getLogByShopthruOrderId($shopthruOrderId);
        }
        $magentoOrderId = $importLog->getMagentoOrderId();
        $order = $this->orderRepository->get($magentoOrderId);

        switch ($this->moduleConfig->getCancelledOrderAction()) {
            case ModuleConfig\Source\CancelledOrderAction::UPDATE_STATUS:
                return $this->cancelOrderStatusAction($order, $importLog);
            case ModuleConfig\Source\CancelledOrderAction::DELETE:
                return $this->cancelOrderDeleteAction($order, $importLog);
            default:
                throw new \Exception('Invalid cancelled order action');
        }
    }

    private function cancelOrderStatusAction(OrderInterface $order, ImportLogInterface $logEntry)
    {
        $order->setState(Order::STATE_CANCELED);
        $order->setStatus($this->moduleConfig->getCancelledOrderStatus() ?: Order::STATE_CANCELED);
        // add note to order
        $order->addStatusHistoryComment('Order cancelled by Shopthru');
        $this->orderRepository->save($order);
        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::ORDER_STATUS,
            'Order cancelled.'
        );
        return true;
    }

    private function cancelOrderDeleteAction(OrderInterface $order, ImportLogInterface $logEntry)
    {
        $this->orderRepository->delete($order);
        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::ORDER_DELETED,
            'Order deleted.'
        );

        return true;
    }

    /**
     * Create an order directly without using quote
     *
     * @param OrderImportInterface $orderData
     * @param ImportLogInterface $logEntry
     * @return \Magento\Sales\Model\Order
     * @throws LocalizedException
     */
    public function create(
        OrderImportInterface $orderData,
        ImportLogInterface $logEntry
    ): Order {
        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::ORDER_CREATING_DIRECT,
            'Creating order directly without quote'
        );

        $order = $this->initializeOrder($orderData);
        $this->setCustomerInformation($order, $orderData, $logEntry);
        $this->setOrderAddresses($order, $orderData, $logEntry);
        $this->addOrderItems($order, $orderData, $logEntry);
        $this->setOrderPayment($order, $orderData, $logEntry);
        $this->setShippingInformation($order, $orderData, $logEntry);
        $this->setOrderTotals($order, $orderData, $logEntry);

        // Set external order ID
        $order->setData('ext_order_id', $orderData->getOrderId());

        // Save the order
        $this->orderRepository->save($order);

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::ORDER_CREATED_DIRECT,
            'Order created',
            ['order_id' => $order->getIncrementId()]
        );

//        // Create invoice if auto-invoice is enabled
//        if ($this->moduleConfig->isAutoInvoiceEnabled()) {
//            $this->createInvoice($order, $logEntry);
//        }
//
//        // Decrement stock if enabled
//        if ($this->moduleConfig->isDecrementStockEnabled()) {
//            $this->decrementStock($order, $logEntry);
//        }

        return $order;
    }

    /**
     * Set billing and shipping addresses on order
     *
     * @param Order $order
     * @param OrderImportInterface $orderData
     * @param ImportLogInterface $logEntry
     */
    private function setOrderAddresses(
        Order $order,
        OrderImportInterface $orderData,
        ImportLogInterface $logEntry
    ): void {
        $customerData = $orderData->getCustomer();

        // Create billing address
        $billingData = $customerData['billing_address'];
        $billingAddress = $this->orderAddressFactory->create();
        $billingAddress->setOrder($order);
        $billingAddress->setAddressType('billing');
        $billingAddress->setFirstname($this->getFirstName($customerData['name']));
        $billingAddress->setLastname($this->getLastName($customerData['name']));
        $billingAddress->setStreet($billingData['street_address']);
        $billingAddress->setCity($billingData['city']);
        $billingAddress->setPostcode($billingData['postcode']);
        $billingAddress->setCountryId($billingData['country']);
        $billingAddress->setRegion($billingData['region'] ?? '');
        $billingAddress->setEmail($customerData['email']);
        $billingAddress->setTelephone($customerData['telephone'] ?? '');

        // Create shipping address
        $shippingData = $customerData['shipping_address'];
        $shippingAddress = $this->orderAddressFactory->create();
        $shippingAddress->setOrder($order);
        $shippingAddress->setAddressType('shipping');
        $shippingAddress->setFirstname($this->getFirstName($customerData['name']));
        $shippingAddress->setLastname($this->getLastName($customerData['name']));
        $shippingAddress->setStreet($shippingData['street_address']);
        $shippingAddress->setCity($shippingData['city']);
        $shippingAddress->setPostcode($shippingData['postcode']);
        $shippingAddress->setCountryId($shippingData['country']);
        $shippingAddress->setRegion($shippingData['region'] ?? '');
        $shippingAddress->setEmail($customerData['email']);
        $shippingAddress->setTelephone($customerData['telephone'] ?? '');

        // Add addresses to order
        $order->setBillingAddress($billingAddress);
        $order->setShippingAddress($shippingAddress);
    }

    /**
     * Set payment information on order
     *
     * @param Order $order
     * @param OrderImportInterface $orderData
     * @param ImportLogInterface $logEntry
     */
    private function setOrderPayment(
        OrderInterface $order,
        OrderImportInterface $orderData,
        ImportLogInterface $logEntry
    ): void {
        $payment = $this->orderPaymentFactory->create();
        $payment->setOrder($order);
        $payment->setMethod(ShopthruPayment::CODE);

        // Set transaction ID if available
        if ($orderData->getPaymentTransactionId()) {
            $payment->setTransactionId($orderData->getPaymentTransactionId());
            $payment->setLastTransId($orderData->getPaymentTransactionId());
        }

        // Mark as captured
//        $payment->setAmountPaid($orderData->getTotalPaid());
//        $payment->setBaseAmountPaid($orderData->getTotalPaid());
        $payment->setAmountOrdered($orderData->getTotalPaid());
        $payment->setBaseAmountOrdered($orderData->getTotalPaid());

        $order->setPayment($payment);
    }

    /**
     * Add items to order
     *
     * @param Order $order
     * @param OrderImportInterface $orderData
     * @param ImportLogInterface $logEntry
     */
    private function addOrderItems(
        OrderInterface $order,
        OrderImportInterface $orderData,
        ImportLogInterface $logEntry
    ): void {

        $storeId = $orderData->getExtStoreId() ? (int)$orderData->getExtStoreId() : null;
        $store = $this->loggingHelper->getStore($storeId);

        $items = $orderData->getItems();
        $itemsSubtotal = 0;
        $totalQty = 0;

        foreach ($items as $itemData) {
            $sku = $itemData['product_sku'];
            $qty = $itemData['quantity'];
            $price = $this->loggingHelper->formatPrice((float)$itemData['price']);
            $rowTotal = $price * $qty;
            $totalQty += $qty;

            try {
                $product = $this->productRepository->get($sku);

                /** @var Order\Item $orderItem */
                $orderItem = $this->orderItemFactory->create();
                $orderItem->setOrder($order);
                $orderItem->setStoreId($store->getId());

                // Apply default item data
                $this->applyDefaultItemData($orderItem, $itemData);

                $orderItem->setProductId($product->getId());
                $orderItem->setProductType($product->getTypeId());
                $orderItem->setName($product->getName());
                $orderItem->setSku($sku);
                $orderItem->setQtyOrdered($qty);
                $orderItem->setPrice($price);
                $orderItem->setBasePrice($price);
                $orderItem->setOriginalPrice($product->getPrice());
                $orderItem->setPriceInclTax($price);
                $orderItem->setBasePriceInclTax($price);

                // Set row total
                $orderItem->setRowTotal($rowTotal);
                $orderItem->setBaseRowTotal($rowTotal);
                $orderItem->setRowTotalInclTax($rowTotal);
                $orderItem->setBaseRowTotalInclTax($rowTotal);

                // Handle discount if available
                if (isset($itemData['discount_amount']) && (float)$itemData['discount_amount'] > 0) {
                    $discountAmount = (float)$itemData['discount_amount'];
                    $orderItem->setDiscountAmount($discountAmount);
                    $orderItem->setBaseDiscountAmount($discountAmount);
                }

                $orderItem->setProductOptions([
                    'info_buyRequest' => [
                        'qty' => $qty,
                        'product' => $product->getId(),
                        'item' => $product->getId()
                    ]
                ]);

                // Add item to order
                $order->addItem($orderItem);

                // Track subtotal
                $itemsSubtotal += $rowTotal;

                $this->loggingHelper->addEventLog(
                    $logEntry,
                    EventType::ORDER_ITEM_ADDED,
                    'Added item to order',
                    [
                        'sku' => $sku,
                        'qty' => $qty,
                        'price' => $price,
                        'row_total' => $rowTotal
                    ]
                );
            } catch (\Exception $e) {
                $this->loggingHelper->addEventLog(
                    $logEntry,
                    EventType::ORDER_ITEM_ERROR,
                    'Error adding item to order: ' . $e->getMessage(),
                    ['sku' => $sku]
                );
                throw $e;
            }
        }

        $order->setTotalQtyOrdered($totalQty);

        // Update order subtotal if different from calculated
        $providedSubtotal = (float)$orderData->getSubTotal();
        if (abs($providedSubtotal - $itemsSubtotal) > 0.001) {
            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::ORDER_SUBTOTAL_ADJUSTED,
                'Adjusting order subtotal to match Shopthru data',
                [
                    'calculated_subtotal' => $itemsSubtotal,
                    'shopthru_subtotal' => $providedSubtotal,
                    'difference' => $providedSubtotal - $itemsSubtotal
                ]
            );

            // Use the provided subtotal
            $order->setSubtotal($providedSubtotal);
            $order->setBaseSubtotal($providedSubtotal);
        }
    }

    private function applyDefaultOrderData(OrderInterface $order, OrderImportInterface $orderData): Order
    {
        $defaultOrderData = $this->defaultData->getDefaultOrderData();
        $appliedDefaults = [];

        foreach ($defaultOrderData as $key => $defaultValue) {
            // Check if value exists in orderData
            $value = $orderData->getData($key);

            // If the value doesn't exist in orderData, use the default
            if ($value === null) {
                $order->setData($key, $defaultValue);
                $appliedDefaults[$key] = $defaultValue;
            }
        }

        // If any defaults were applied, log them
        if (!empty($appliedDefaults)) {
            $this->loggingHelper->logDebug(
                'Applied default order data',
                ['applied_defaults' => $appliedDefaults]
            );
        }

        return $order;
    }

    /**
     * Apply default values to order item
     *
     * @param Order\Item $orderItem
     * @param array $itemData
     * @return Order\Item
     */
    private function applyDefaultItemData(Order\Item $orderItem, array $itemData): Order\Item
    {
        $defaultItemData = $this->defaultData->getDefaultItemData();

        foreach ($defaultItemData as $key => $defaultValue) {
            // Check if value exists in itemData
            $value = $itemData[$key] ?? null;

            // If the value doesn't exist in itemData, use the default
            if ($value === null) {
                $orderItem->setData($key, $defaultValue);
            }
        }

        return $orderItem;
    }

    private function createInvoice(OrderInterface $order, ImportLogInterface $logEntry): ?\Magento\Sales\Model\Order\Invoice
    {
        try {
            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::INVOICE_CREATING,
                'Creating invoice for order',
                ['order_id' => $order->getIncrementId()]
            );

            if ($order->canInvoice()) {
                $invoice = $order->prepareInvoice();
                $invoice->register();
                $invoice->pay();

                // Explicitly mark order as paid
                $order->setTotalPaid($order->getGrandTotal());
                $order->setBaseTotalPaid($order->getBaseGrandTotal());

                // Mark order as processing
                $order->setState(Order::STATE_PROCESSING);
                $order->setStatus($this->moduleConfig->getOrderStatus() ?: Order::STATE_PROCESSING);
                $order->setIsInProcess(true);

                $transaction = $this->transactionFactory->create();
                $transaction->addObject(
                    $invoice
                )->addObject(
                    $order
                );

                $transaction->save();

                // Log invoice creation success
                $this->loggingHelper->addEventLog(
                    $logEntry,
                    EventType::INVOICE_CREATED,
                    'Invoice created and marked as paid',
                    [
                        'invoice_id' => $invoice->getIncrementId(),
                        'invoice_total' => $invoice->getGrandTotal(),
                        'order_state' => $order->getState(),
                        'order_status' => $order->getStatus()
                    ]
                );

                return $invoice;
            } else {
                $this->loggingHelper->addEventLog(
                    $logEntry,
                    EventType::INVOICE_SKIPPED,
                    'Cannot create invoice for order',
                    [
                        'reason' => 'Order cannot be invoiced',
                        'order_state' => $order->getState(),
                        'payment_state' => $order->getPayment()->getData('additional_information')
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::INVOICE_ERROR,
                'Error creating invoice: ' . $e->getMessage(),
                [
                    'trace' => $e->getTraceAsString(),
                    'order_state' => $order->getState(),
                    'payment_method' => $order->getPayment()->getMethod()
                ]
            );
        }

        return null;
    }

    /**
     * Decrement stock for order items
     *
     * @param Order $order
     * @param ImportLogInterface $logEntry
     */
    private function decrementStock(OrderInterface $order, ImportLogInterface $logEntry): void
    {
        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::STOCK_DECREMENTING,
            'Decrementing stock for order items',
            ['order_id' => $order->getIncrementId()]
        );

        try {
            $stockUpdates = $this->orderProcessesHelper->decrementStock($order);
        } catch (\Exception $e) {
            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::STOCK_DECREMENT_ERROR,
                'Error updating stock: ' . $e->getMessage(),
                []
            );
        }

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::STOCK_DECREMENTED,
            'Stock decremented for order items',
            ['stock_updates' => $stockUpdates]
        );
    }

    /**
     * Send order confirmation email
     *
     * @param Order $order
     * @param ImportLogInterface $logEntry
     * @return bool
     */
    public function sendOrderEmailIfEnabled(OrderInterface $order, ImportLogInterface $logEntry): bool
    {
        // Check if email sending is enabled in the configuration
        if (!$this->moduleConfig->isTriggerEmailEnabled()) {
            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::EMAIL_SKIPPED,
                'Email sending is disabled in configuration',
                ['order_id' => $order->getIncrementId()]
            );
            return false;
        }

        try {
            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::EMAIL_SENDING,
                'Sending order confirmation email',
                ['order_id' => $order->getIncrementId()]
            );

            // Send the order email
            $emailSent = $this->orderProcessesHelper->sendOrderConfirmationEmail($order);

            if ($emailSent) {
                $this->loggingHelper->addEventLog(
                    $logEntry,
                    EventType::EMAIL_SENT,
                    'Order confirmation email sent successfully',
                    ['order_id' => $order->getIncrementId()]
                );
                return true;
            } else {
                $this->loggingHelper->addEventLog(
                    $logEntry,
                    EventType::EMAIL_ERROR,
                    'Failed to send order confirmation email',
                    ['order_id' => $order->getIncrementId()]
                );
                return false;
            }
        } catch (\Exception $e) {
            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::EMAIL_ERROR,
                'Error sending order confirmation email: ' . $e->getMessage(),
                [
                    'order_id' => $order->getIncrementId(),
                    'error' => $e->getMessage()
                ]
            );
            return false;
        }
    }

    /**
     * Extract first name from full name
     *
     * @param string $name
     * @return string
     */
    private function getFirstName(string $name): string
    {
        $nameParts = explode(' ', trim($name), 2);
        return $nameParts[0];
    }

    /**
     * Extract last name from full name
     *
     * @param string $name
     * @return string
     */
    private function getLastName(string $name): string
    {
        $nameParts = explode(' ', trim($name), 2);
        return isset($nameParts[1]) ? $nameParts[1] : '.';
    }
}
