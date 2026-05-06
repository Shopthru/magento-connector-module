<?php

declare(strict_types=1);

namespace Shopthru\Connector\Test\Integration\Api;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Shopthru\Connector\Api\Data\ImportLogInterface;
use Shopthru\Connector\Api\Data\OrderImportResponseInterface;
use Shopthru\Connector\Api\ImportLogRepositoryInterface;
use Shopthru\Connector\Api\ImportOrderManagementInterface;
use Shopthru\Connector\Model\CancelOrderRequest;
use Shopthru\Connector\Model\ConfirmOrderRequest;
use Shopthru\Connector\Model\EventType;
use Shopthru\Connector\Model\OrderImport;
use Shopthru\Connector\Model\Payment\Method\Shopthru;

/**
 * @magentoAppArea webapi_rest
 */
class ImportOrderManagementTest extends TestCase
{
    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture current_store shopthru/general/validate_available_stock 0
     * @magentoConfigFixture current_store shopthru/general/link_customer 0
     * @magentoConfigFixture current_store payment/shopthru/active 1
     */
    public function testImportOrderCreatesPendingPaymentMagentoOrderAndImportLog(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $shopthruOrderId = 'shopthru-integration-' . uniqid();

        $service = $objectManager->get(ImportOrderManagementInterface::class);
        $response = $service->importOrder($this->createOrderImportPayload($shopthruOrderId));

        $this->assertCreateResponse($response, $shopthruOrderId);
        $order = $objectManager->get(OrderRepositoryInterface::class)->get((int)$response->getMagentoOrderId());
        $this->assertPendingPaymentOrder($order);
        $this->assertSimpleOrderItem($order);

        $importLog = $objectManager->get(ImportLogRepositoryInterface::class)->getById($response->getImportLogId());
        $this->assertImportLog($importLog, $shopthruOrderId, $order);
        $this->assertSuccessfulImportEvents($importLog);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture current_store shopthru/general/validate_available_stock 0
     * @magentoConfigFixture current_store shopthru/general/link_customer 0
     * @magentoConfigFixture current_store payment/shopthru/active 1
     */
    public function testImportMultipleOrdersCreatesPendingPaymentMagentoOrdersAndImportLogs(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $firstShopthruOrderId = 'shopthru-integration-multi-first-' . uniqid();
        $secondShopthruOrderId = 'shopthru-integration-multi-second-' . uniqid();

        $service = $objectManager->get(ImportOrderManagementInterface::class);
        $responses = $service->importMultipleOrders([
            $this->createOrderImportPayload($firstShopthruOrderId),
            $this->createOrderImportPayload($secondShopthruOrderId),
        ]);

        $this->assertSame([], $responses);

        $firstImportLog = $objectManager->get(ImportLogRepositoryInterface::class)
            ->getByShopthruOrderId($firstShopthruOrderId);
        $firstOrder = $objectManager->get(OrderRepositoryInterface::class)->get((int)$firstImportLog->getMagentoOrderId());
        $this->assertPendingPaymentOrder($firstOrder);
        $this->assertSimpleOrderItem($firstOrder);
        $this->assertImportLog($firstImportLog, $firstShopthruOrderId, $firstOrder);
        $this->assertSuccessfulImportEvents($firstImportLog);

        $secondImportLog = $objectManager->get(ImportLogRepositoryInterface::class)
            ->getByShopthruOrderId($secondShopthruOrderId);
        $secondOrder = $objectManager->get(OrderRepositoryInterface::class)->get((int)$secondImportLog->getMagentoOrderId());
        $this->assertPendingPaymentOrder($secondOrder);
        $this->assertSimpleOrderItem($secondOrder);
        $this->assertImportLog($secondImportLog, $secondShopthruOrderId, $secondOrder);
        $this->assertSuccessfulImportEvents($secondImportLog);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture current_store shopthru/general/validate_available_stock 0
     * @magentoConfigFixture current_store shopthru/general/link_customer 0
     * @magentoConfigFixture current_store shopthru/general/auto_invoice 0
     * @magentoConfigFixture current_store shopthru/general/decrement_stock 0
     * @magentoConfigFixture current_store shopthru/general/trigger_email 0
     * @magentoConfigFixture current_store shopthru/general/order_status processing
     * @magentoConfigFixture current_store payment/shopthru/active 1
     */
    public function testCompleteOrderConfirmsPendingPaymentMagentoOrderAndImportLog(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $shopthruOrderId = 'shopthru-integration-confirm-' . uniqid();
        $transactionId = 'shopthru-transaction-' . uniqid();

        $service = $objectManager->get(ImportOrderManagementInterface::class);
        $createResponse = $service->importOrder($this->createOrderImportPayload($shopthruOrderId));
        $this->assertCreateResponse($createResponse, $shopthruOrderId);

        $confirmResponse = $service->completeOrder(
            $shopthruOrderId,
            $this->createConfirmOrderRequest($shopthruOrderId, $transactionId)
        );

        $this->assertSame(OrderImportResponseInterface::IMPORT_ACTION_CONFIRM, $confirmResponse->getImportAction());
        $this->assertTrue($confirmResponse->getImportActionSuccess());
        $this->assertSame(ImportLogInterface::STATUS_SUCCESS, $confirmResponse->getImportStatus());
        $this->assertSame($shopthruOrderId, $confirmResponse->getShopthruOrderId());
        $this->assertSame($createResponse->getMagentoOrderId(), $confirmResponse->getMagentoOrderId());
        $this->assertSame($createResponse->getImportLogId(), $confirmResponse->getImportLogId());

        $order = $objectManager->get(OrderRepositoryInterface::class)->get((int)$confirmResponse->getMagentoOrderId());
        $this->assertSame(Order::STATE_PROCESSING, $order->getState());
        $this->assertSame(Order::STATE_PROCESSING, $order->getStatus());
        $this->assertSame('integration.customer@example.com', $order->getCustomerEmail());
        $this->assertSame(Shopthru::CODE, $order->getPayment()->getMethod());
        $this->assertSame($transactionId, $order->getPayment()->getLastTransId());
        $this->assertEquals(10.00, (float)$order->getGrandTotal());
        $this->assertEquals(10.00, (float)$order->getTotalPaid());
        $this->assertSimpleOrderItem($order);

        $importLog = $objectManager->get(ImportLogRepositoryInterface::class)->getById($confirmResponse->getImportLogId());
        $this->assertSame(ImportLogInterface::STATUS_SUCCESS, $importLog->getStatus());
        $this->assertSame($shopthruOrderId, $importLog->getShopthruOrderId());
        $this->assertSame((string)$order->getEntityId(), $importLog->getMagentoOrderId());
        $this->assertSame($shopthruOrderId, $importLog->getShopthruData()['order_id']);

        $eventNames = array_column($importLog->getLogData(), 'event');
        $this->assertContains(EventType::IMPORT_STARTED, $eventNames);
        $this->assertContains(EventType::IMPORT_COMPLETED, $eventNames);
        $this->assertContains(EventType::ORDER_COMPLETION_STARTED, $eventNames);
        $this->assertContains(EventType::ORDER_COMPLETION_COMPLETED, $eventNames);
    }

    /**
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoConfigFixture current_store shopthru/general/validate_available_stock 0
     * @magentoConfigFixture current_store shopthru/general/link_customer 0
     * @magentoConfigFixture current_store shopthru/general/cancelled_order_action update_status
     * @magentoConfigFixture current_store shopthru/general/cancelled_order_status canceled
     * @magentoConfigFixture current_store payment/shopthru/active 1
     */
    public function testCancelOrderCancelsPendingPaymentMagentoOrderAndImportLog(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $shopthruOrderId = 'shopthru-integration-cancel-' . uniqid();

        $service = $objectManager->get(ImportOrderManagementInterface::class);
        $createResponse = $service->importOrder($this->createOrderImportPayload($shopthruOrderId));
        $this->assertCreateResponse($createResponse, $shopthruOrderId);

        $cancelResponse = $service->cancelOrder(
            $shopthruOrderId,
            $this->createCancelOrderRequest()
        );

        $this->assertSame(OrderImportResponseInterface::IMPORT_ACTION_CANCEL, $cancelResponse->getImportAction());
        $this->assertTrue($cancelResponse->getImportActionSuccess());
        $this->assertSame(ImportLogInterface::STATUS_CANCELLED, $cancelResponse->getImportStatus());
        $this->assertSame($shopthruOrderId, $cancelResponse->getShopthruOrderId());
        $this->assertSame($createResponse->getMagentoOrderId(), $cancelResponse->getMagentoOrderId());
        $this->assertSame($createResponse->getImportLogId(), $cancelResponse->getImportLogId());

        $order = $objectManager->get(OrderRepositoryInterface::class)->get((int)$cancelResponse->getMagentoOrderId());
        $this->assertSame(Order::STATE_CANCELED, $order->getState());
        $this->assertSame(Order::STATE_CANCELED, $order->getStatus());
        $this->assertSame('integration.customer@example.com', $order->getCustomerEmail());
        $this->assertSame(Shopthru::CODE, $order->getPayment()->getMethod());
        $this->assertEquals(10.00, (float)$order->getGrandTotal());
        $this->assertSimpleOrderItem($order);

        $importLog = $objectManager->get(ImportLogRepositoryInterface::class)->getById($cancelResponse->getImportLogId());
        $this->assertSame(ImportLogInterface::STATUS_CANCELLED, $importLog->getStatus());
        $this->assertSame($shopthruOrderId, $importLog->getShopthruOrderId());
        $this->assertSame((string)$order->getEntityId(), $importLog->getMagentoOrderId());
        $this->assertSame($shopthruOrderId, $importLog->getShopthruData()['order_id']);

        $eventNames = array_column($importLog->getLogData(), 'event');
        $this->assertContains(EventType::IMPORT_STARTED, $eventNames);
        $this->assertContains(EventType::IMPORT_COMPLETED, $eventNames);
        $this->assertContains(EventType::ORDER_CANCELLATION_STARTED, $eventNames);
        $this->assertContains(EventType::ORDER_CANCELLED, $eventNames);
    }

    private function assertCreateResponse(
        OrderImportResponseInterface $response,
        string $shopthruOrderId
    ): void {
        $this->assertSame(OrderImportResponseInterface::IMPORT_ACTION_CREATE, $response->getImportAction());
        $this->assertTrue($response->getImportActionSuccess());
        $this->assertSame(ImportLogInterface::STATUS_PENDING_PAYMENT, $response->getImportStatus());
        $this->assertSame($shopthruOrderId, $response->getShopthruOrderId());
        $this->assertNotEmpty($response->getMagentoOrderId());
        $this->assertNotEmpty($response->getImportLogId());
    }

    private function assertPendingPaymentOrder(OrderInterface $order): void
    {
        $this->assertSame(Order::STATE_PENDING_PAYMENT, $order->getState());
        $this->assertSame(Order::STATE_PENDING_PAYMENT, $order->getStatus());
        $this->assertSame('integration.customer@example.com', $order->getCustomerEmail());
        $this->assertSame(Shopthru::CODE, $order->getPayment()->getMethod());
        $this->assertEquals(10.00, (float)$order->getGrandTotal());
    }

    private function assertSimpleOrderItem(OrderInterface $order): void
    {
        $items = $order->getAllItems();
        $this->assertCount(1, $items);

        $item = reset($items);
        $this->assertSame('simple', $item->getSku());
        $this->assertEquals(1.0, (float)$item->getQtyOrdered());
        $this->assertEquals(10.00, (float)$item->getPrice());
    }

    private function assertImportLog(
        ImportLogInterface $importLog,
        string $shopthruOrderId,
        OrderInterface $order
    ): void {
        $this->assertSame(ImportLogInterface::STATUS_PENDING_PAYMENT, $importLog->getStatus());
        $this->assertSame($shopthruOrderId, $importLog->getShopthruOrderId());
        $this->assertSame((string)$order->getEntityId(), $importLog->getMagentoOrderId());
        $this->assertSame($shopthruOrderId, $importLog->getShopthruData()['order_id']);
    }

    private function assertSuccessfulImportEvents(ImportLogInterface $importLog): void
    {
        $eventNames = array_column($importLog->getLogData(), 'event');
        $this->assertContains(EventType::IMPORT_STARTED, $eventNames);
        $this->assertContains(EventType::ORDER_CREATED_DIRECT, $eventNames);
        $this->assertContains(EventType::IMPORT_COMPLETED, $eventNames);
    }

    private function createConfirmOrderRequest(string $shopthruOrderId, string $transactionId): ConfirmOrderRequest
    {
        return new ConfirmOrderRequest([
            'order_id' => $shopthruOrderId,
            'transaction_id' => $transactionId,
            'payment_data' => [
                'transaction_id' => $transactionId,
            ],
        ]);
    }

    private function createCancelOrderRequest(): CancelOrderRequest
    {
        return new CancelOrderRequest([
            'reason_code' => 'payment_failed',
            'reason_text' => 'Payment failed in Shopthru checkout',
            'cancel_data' => [
                'source' => 'integration-test',
            ],
        ]);
    }

    private function createOrderImportPayload(string $shopthruOrderId): OrderImport
    {
        return new OrderImport([
            'order_id' => $shopthruOrderId,
            'publisher' => [
                'name' => 'Shopthru Integration Test',
                'ref' => 'STIT',
            ],
            'primary_short_code' => 'simple',
            'primary_sku' => 'simple',
            'status' => 'pending',
            'currency' => 'GBP',
            'locale' => 'en-GB',
            'sub_total' => 10.00,
            'tax_total' => 0.00,
            'total_excl_tax' => 10.00,
            'total_paid' => 10.00,
            'total' => 10.00,
            'shipping_total' => 0.00,
            'purchase_url' => null,
            'discount_total' => 0.00,
            'commission_total' => 0.00,
            'discount_codes_applied' => null,
            'checkout_domain' => null,
            'ext_store_id' => '1',
            'ext_attributes' => [],
            'payment_method' => 'shopthru',
            'payment_transaction_id' => null,
            'payment_data' => [],
            'created_at' => '2026-05-05T10:15:30Z',
            'shipping_method' => 'flatrate_flatrate',
            'shipping_title' => 'Flat Rate Shipping',
            'items' => [
                [
                    'order_item_id' => $shopthruOrderId . '-item-1',
                    'order_id' => $shopthruOrderId,
                    'product_sku' => 'simple',
                    'product_name' => 'Simple Product',
                    'quantity' => 1,
                    'price' => 10.00,
                    'discount_amount' => 0.00,
                    'row_total' => 10.00,
                ],
            ],
            'customer' => [
                'name' => 'Integration Customer',
                'email' => 'integration.customer@example.com',
                'telephone' => '07123456789',
                'billing_address' => [
                    'street_address' => [
                        '1 Integration Street',
                    ],
                    'city' => 'Brighton',
                    'postcode' => 'BN1 1AA',
                    'country' => 'GB',
                ],
                'shipping_address' => [
                    'street_address' => [
                        '1 Integration Street',
                    ],
                    'city' => 'Brighton',
                    'postcode' => 'BN1 1AA',
                    'country' => 'GB',
                ],
            ],
        ]);
    }
}
