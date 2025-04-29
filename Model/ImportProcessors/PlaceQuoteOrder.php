<?php

namespace Shopthru\Connector\Model\ImportProcessors;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Api\Data\StoreInterface;
use Shopthru\Connector\Api\Data\ImportLogInterface;
use Shopthru\Connector\Api\Data\OrderImportInterface;
use Shopthru\Connector\Helper\Logging as LoggingHelper;
use Shopthru\Connector\Helper\OrderProcesses;
use Shopthru\Connector\Model\Config as ModuleConfig;
use Shopthru\Connector\Model\EventType;
use Shopthru\Connector\Model\ShippingMethod;

class PlaceQuoteOrder
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CartManagementInterface $cartManagement,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly QuoteFactory $quoteFactory,
        private readonly LoggingHelper $loggingHelper,
        private readonly ModuleConfig $moduleConfig,
        private readonly OrderProcesses $orderProcessesHelper,
    ) {}

    public function create(
        OrderImportInterface $orderData,
        ImportLogInterface $logEntry
    ): OrderInterface {

        $storeId = $orderData->getExtStoreId() ? (int)$orderData->getExtStoreId() : null;
        $store = $this->loggingHelper->getStore($storeId);


        $customer = $this->prepareCustomer($orderData, $store, $logEntry);

        // Create quote
        $quote = $this->prepareQuote($orderData, $customer, $store, $logEntry);

        // Create order from quote
        $order = $this->createOrder($quote, $orderData, $logEntry);

        return $order;
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
     * Prepare quote with all necessary data, applying shipping override *after* initial totals.
     *
     * @param OrderImportInterface $orderData
     * @param int|null $customerId
     * @param StoreInterface $store
     * @param ImportLogInterface $logEntry
     * @return CartInterface
     * @throws LocalizedException|NoSuchEntityException
     */
    private function prepareQuote(
        OrderImportInterface $orderData,
        ?int $customerId,
        StoreInterface $store,
        ImportLogInterface $logEntry
    ): CartInterface {
        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::QUOTE_CREATING,
            'Creating new quote'
        );

        $quote = $this->quoteFactory->create();
        $quote->setStore($store);
        $quote->setIsMultiShipping(false);
        $quote->setInventoryProcessed(false);
        $quote->setIsSuperMode(true); // Skip validation

        // Set currency and store
        $quote->setCurrency();

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::QUOTE_CREATED,
            'Quote created',
            ['quote_id' => $quote->getId()]
        );

        // Set customer data
        if ($customerId) {
            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::QUOTE_CUSTOMER,
                'Assigning registered customer to quote',
                ['customer_id' => $customerId]
            );

            $quote->assignCustomerWithAddressChange(
                $this->customerModelFactory->create()->load($customerId)
            );
        } else {
            // Set as guest checkout
            $customerData = $orderData->getCustomer();

            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::QUOTE_GUEST,
                'Setting up guest checkout',
                ['email' => $customerData['email']]
            );

            $quote->setCustomerIsGuest(1);
            $quote->setCustomerEmail($customerData['email']);
            $quote->setCustomerFirstname($this->getFirstName($customerData['name']));
            $quote->setCustomerLastname($this->getLastName($customerData['name']));
        }

        // TODO: Set coupon code if available
        // TODO: save quote using repository
        $quote->save();

        // Add products to quote
        $this->addProductsToQuote($quote, $orderData, $logEntry);

        // Set addresses
        $this->setBillingAddress($quote, $orderData, $logEntry);
        $this->setShippingAddress($quote, $orderData, $logEntry);

        // Set payment method
        $this->setPaymentMethod($quote, $orderData, $logEntry);

        // Set shipping method
        $this->setShippingMethod($quote, $orderData, $logEntry);

        // Collect totals and save quote
        $quote->collectTotals();
        $this->cartRepository->save($quote);

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::QUOTE_FINALIZED, // Use Model Constant
            'Quote finalized and saved with forced shipping amount',
            [
                'quote_id' => $quote->getId(),
                'subtotal' => $quote->getSubtotal(),
                'grand_total' => $quote->getGrandTotal()
            ]
        );

        return $quote;
    }

    /**
     * Add products to quote
     *
     * @param Quote $quote
     * @param OrderImportInterface $orderData
     * @param ImportLogInterface $logEntry
     * @throws LocalizedException
     */
    private function addProductsToQuote(
        Quote $quote,
        OrderImportInterface $orderData,
        ImportLogInterface $logEntry
    ): void {
        $items = $orderData->getItems();

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::QUOTE_ITEMS_ADDING,
            'Adding items to quote',
            ['item_count' => count($items)]
        );

        foreach ($items as $item) {
            $sku = $item['product_sku'];
            $qty = $item['quantity'];
            $price = $this->loggingHelper->formatPrice((float)$item['price']);

            try {
                $product = $this->productRepository->get($sku);

                $this->loggingHelper->addEventLog(
                    $logEntry,
                    EventType::QUOTE_ITEM_ADDING,
                    'Adding item to quote: ' . $product->getName(),
                    [
                        'sku' => $sku,
                        'qty' => $qty,
                        'price' => $price
                    ]
                );

                $quoteItem = $quote->addProduct($product, new DataObject(['qty' => $qty]));

                // Set custom price if needed
                if (abs($price - $product->getFinalPrice()) > 0.001) {
                    $quoteItem->setCustomPrice($price);
                    $quoteItem->setOriginalCustomPrice($price);

                    $this->loggingHelper->addEventLog(
                        $logEntry,
                        EventType::QUOTE_ITEM_CUSTOM_PRICE,
                        'Applied custom price to item',
                        [
                            'sku' => $sku,
                            'custom_price' => $price,
                            'original_price' => $product->getFinalPrice()
                        ]
                    );
                }

                if (isset($item['discount_amount']) && is_numeric($item['discount_amount'])) {
                    $discountAmount = abs((float)$item['discount_amount']);
                    $rowTotal = (float)$item['price'] * (int)$item['quantity'];

                    // Store this for later discount application
                    $quoteItem->setData('shopthru_discount_amount', $discountAmount);

                    $this->loggingHelper->addEventLog(
                        $logEntry,
                        EventType::QUOTE_ITEM_DISCOUNT,
                        'Applied item discount',
                        [
                            'sku' => $sku,
                            'discount_amount' => $discountAmount,
                            'row_total' => $rowTotal
                        ]
                    );
                }

                $quoteItem->save();

                $this->loggingHelper->addEventLog(
                    $logEntry,
                    EventType::QUOTE_ITEM_ADDED,
                    'Item added to quote',
                    [
                        'sku' => $sku,
                        'quote_item_id' => $quoteItem->getId()
                    ]
                );
            } catch (NoSuchEntityException $e) {
                $this->loggingHelper->addEventLog(
                    $logEntry,
                    EventType::QUOTE_ITEM_ERROR,
                    'Product not found: ' . $sku,
                    ['error' => $e->getMessage()]
                );
                throw new LocalizedException(__('Product with SKU "%1" not found', $sku));
            }
        }

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::QUOTE_ITEMS_ADDED,
            'All items added to quote',
            ['items_count' => count($quote->getAllItems())]
        );
    }

    /**
     * Set billing address on quote
     *
     * @param Quote $quote
     * @param OrderImportInterface $orderData
     * @param ImportLogInterface $logEntry
     */
    private function setBillingAddress(
        Quote $quote,
        OrderImportInterface $orderData,
        ImportLogInterface $logEntry
    ): void {
        $customerData = $orderData->getCustomer();
        $billingData = $customerData['billing_address'];

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::QUOTE_ADDRESS_BILLING,
            'Setting billing address'
        );

        $billingAddress = $quote->getBillingAddress();
        $billingAddress->setFirstname($this->getFirstName($customerData['name']));
        $billingAddress->setLastname($this->getLastName($customerData['name']));
        $billingAddress->setStreet($billingData['street_address']);
        $billingAddress->setCity($billingData['city']);
        $billingAddress->setPostcode($billingData['postcode']);
        $billingAddress->setCountryId($billingData['country']);
        $billingAddress->setRegion($billingData['region'] ?? '');
        $billingAddress->setEmail($customerData['email']);
        $billingAddress->setTelephone($customerData['telephone'] ?? '');
        $billingAddress->setSameAsBilling(0);

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::QUOTE_ADDRESS_BILLING_SET,
            'Billing address set',
            [
                'city' => $billingData['city'],
                'country' => $billingData['country']
            ]
        );
    }

    /**
     * Set shipping address on quote and mark for free shipping initially.
     * The actual amount will be set *after* collectTotals.
     *
     * @param Quote $quote
     * @param OrderImportInterface $orderData
     * @param ImportLogInterface $logEntry
     */
    private function setShippingAddress(
        Quote $quote,
        OrderImportInterface $orderData,
        ImportLogInterface $logEntry
    ): void {
        $customerData = $orderData->getCustomer();
        $shippingData = $customerData['shipping_address'];

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::QUOTE_ADDRESS_SHIPPING,
            'Setting shipping address and marking for free shipping override'
        );
        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->setFirstname($this->getFirstName($customerData['name']));
        $shippingAddress->setLastname($this->getLastName($customerData['name']));
        $shippingAddress->setStreet($shippingData['street_address']);
        $shippingAddress->setCity($shippingData['city']);
        $shippingAddress->setPostcode($shippingData['postcode']);
        $shippingAddress->setCountryId($shippingData['country']);
        $shippingAddress->setRegion($shippingData['region'] ?? '');
        $shippingAddress->setEmail($customerData['email']);
        $shippingAddress->setTelephone($customerData['telephone'] ?? '');

        // Set shipping method
        $shippingMethodCode = $orderData->getShippingMethod() ?? ShippingMethod::FLATRATE;
        $shippingMethodDescription = ShippingMethod::getTitle($shippingMethodCode) ?? 'Shipping';

        $shippingAmount = $this->loggingHelper->formatPrice($orderData->getShippingTotal() ?? 0);

        $shippingAddress->setShippingMethod($shippingMethodCode);
        $shippingAddress->setShippingDescription($shippingMethodDescription);
        $shippingAddress->setShippingAmount($shippingAmount);
        $shippingAddress->setBaseShippingAmount($shippingAmount);
        $shippingAddress->setCollectShippingRates(true);
        $shippingAddress->collectShippingRates();

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::QUOTE_ADDRESS_SHIPPING_SET,
            'Shipping address set',
            [
                'city' => $shippingData['city'],
                'country' => $shippingData['country'],
                'shipping_method' => $shippingMethodCode,
                'shipping_amount' => $shippingAmount
            ]
        );
    }

    /**
     * Set payment method on quote
     *
     * @param Quote $quote
     * @param OrderImportInterface $orderData
     * @param ImportLogInterface $logEntry
     */
    private function setPaymentMethod(
        Quote $quote,
        OrderImportInterface $orderData,
        ImportLogInterface $logEntry
    ): void {
        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::QUOTE_PAYMENT,
            'Setting payment method',
            ['method' => 'shopthru']
        );

        $payment = $quote->getPayment();
        $payment->setMethod('shopthru');

        $payment->setAdditionalInformation('payment_captured', true);

        // Store the total paid amount
        if ($orderData->getTotalPaid()) {
            $payment->setAdditionalInformation('amount_paid', $orderData->getTotalPaid());
        }

        // Set transaction ID if available
        if ($orderData->getPaymentTransactionId()) {
            $payment->setAdditionalInformation(
                'transaction_id',
                $orderData->getPaymentTransactionId()
            );

            // Also set as last transaction id
            $payment->setLastTransId($orderData->getPaymentTransactionId());

            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::QUOTE_PAYMENT_TRANSACTION,
                'Set payment transaction ID',
                ['transaction_id' => $orderData->getPaymentTransactionId()]
            );
        }

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::QUOTE_PAYMENT_SET,
            'Payment method set'
        );
    }

    /**
     * Set shipping method on quote
     *
     * @param Quote $quote
     * @param OrderImportInterface $orderData
     * @param ImportLogInterface $logEntry
     */
    private function setShippingMethod(
        Quote $quote,
        OrderImportInterface $orderData,
        ImportLogInterface $logEntry
    ): void {
        $shippingAddress = $quote->getShippingAddress();
        $shippingAmount = $this->loggingHelper->formatPrice($orderData->getShippingTotal() ?? 0);
        $shippingMethodCode = $orderData->getShippingMethod() ?: 'flatrate_flatrate'; // Ensure valid method
        $shippingMethodTitle = $orderData->getShippingTitle() ?: 'Shopthru Shipping';

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::QUOTE_SHIPPING_METHOD,
            'Setting shipping method',
            [
                'method' => $shippingMethodCode,
                'title' => $shippingMethodTitle,
                'amount' => $shippingAmount
            ]
        );

        // First, collect shipping rates so Magento knows what's available
        $shippingAddress->setCollectShippingRates(true);
        $shippingAddress->collectShippingRates();

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::QUOTE_SHIPPING_METHOD_SET,
            'Shipping method set'
        );

        // Save the shipping address to ensure the shipping method sticks
        $shippingAddress->save();
    }

    /**
     * Create order from quote
     *
     * @param CartInterface $quote
     * @param OrderImportInterface $orderData
     * @param ImportLogInterface $logEntry
     * @return Order
     * @throws LocalizedException
     */
    private function createOrder(
        CartInterface $quote,
        OrderImportInterface $orderData,
        ImportLogInterface $logEntry
    ): OrderInterface {

        // First collect totals
        $quote->collectTotals();

        // CRITICAL: Apply shipping amount one last time right before order placement
        $shippingTotal = (float)$orderData->getShippingTotal() ?: 0;
//        $this->forceShippingAmount($quote, $shippingTotal);

        // Save quote one more time with our forced values
        $this->cartRepository->save($quote);

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::ORDER_CREATING,
            'Creating order from quote',
            ['quote_id' => $quote->getId()]
        );

        // Create order
        $orderId = $this->cartManagement->placeOrder($quote->getId());
        $order = $this->orderRepository->get($orderId);

        // Double-check shipping on the created order
        if (abs($order->getShippingAmount() - $shippingTotal) > 0.001) {
            // Shipping amount still got changed, fix it directly on the order
            $order->setShippingAmount($shippingTotal);
            $order->setBaseShippingAmount($shippingTotal);

            // Recalculate grand total
            $order->setGrandTotal($order->getSubtotal() - abs($order->getDiscountAmount()) + $shippingTotal);
            $order->setBaseGrandTotal($order->getBaseSubtotal() - abs($order->getBaseDiscountAmount()) + $shippingTotal);

            $this->loggingHelper->addEventLog(
                $logEntry,
                EventType::ORDER_SHIPPING_CORRECTED,
                'Corrected shipping amount on final order',
                [
                    'original_shipping' => $order->getOrigData('shipping_amount'),
                    'corrected_shipping' => $shippingTotal
                ]
            );

            // Save the corrected order
            $this->orderRepository->save($order);
        }

        $this->storeDiscountInformation($order, $orderData);

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::ORDER_CREATED,
            'Order created',
            [
                'order_id' => $order->getIncrementId(),
                'grand_total' => $order->getGrandTotal()
            ]
        );

        // Set order status based on configuration or Shopthru data
        $configStatus = $this->moduleConfig->getOrderStatus();
        $status = $configStatus ?: ($orderData->getStatus() ?? 'processing');

        $order->setStatus($status);

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::ORDER_STATUS,
            'Setting order status',
            ['status' => $status]
        );

        // Save Shopthru order ID in order
        $order->setData('ext_order_id', $orderData->getOrderId());

        // Create invoice if auto-invoice is enabled
        if ($this->moduleConfig->isAutoInvoiceEnabled()) {
            $this->createInvoice($order, $logEntry);
        }

        // Save order
        $this->orderRepository->save($order);

        $this->loggingHelper->addEventLog(
            $logEntry,
            EventType::ORDER_SAVED,
            'Order saved',
            ['order_id' => $order->getIncrementId()]
        );

        return $order;
    }

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

    private function storeDiscountInformation(OrderInterface $order, OrderImportInterface $orderData): void
    {
        if ($orderData->getDiscountCodesApplied()) {
            try {
                // Add as order comment
                $comment = "External discount code applied: " . $orderData->getDiscountCodesApplied();
                $order->addCommentToStatusHistory($comment);

                // Or store as custom attribute
                $order->setData('shopthru_discount_code', $orderData->getDiscountCodesApplied());

                $this->orderRepository->save($order);
            } catch (\Exception $e) {
                $this->loggingHelper->logError("Could not store discount information: " . $e->getMessage());
            }
        }
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
                $invoice = $this->orderProcessesHelper->createInvoice($order);

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
            $this->loggingHelper->logError('Error creating invoice: ' . $e->getMessage());
        }

        return null;
    }
}
