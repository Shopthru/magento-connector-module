<?php

declare(strict_types=1);

namespace Shopthru\Connector\Test\Integration\Api;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Shopthru\Connector\Api\Data\ImportLogInterface;
use Shopthru\Connector\Api\Data\OrderImportResponseInterface;
use Shopthru\Connector\Api\ImportLogRepositoryInterface;
use Shopthru\Connector\Api\ImportOrderManagementInterface;
use Shopthru\Connector\Model\ConfirmOrderRequest;
use Shopthru\Connector\Model\OrderImport;

/**
 * @magentoAppArea webapi_rest
 */
class ImportLogRepositoryTest extends TestCase
{
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
    public function testGetListReturnsImportLogsMatchingSearchCriteria(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $shopthruOrderId = 'shopthru-integration-logs-' . uniqid();
        $transactionId = 'shopthru-logs-transaction-' . uniqid();

        $importOrderManagement = $objectManager->get(ImportOrderManagementInterface::class);
        $createResponse = $importOrderManagement->importOrder($this->createOrderImportPayload($shopthruOrderId));
        $confirmResponse = $importOrderManagement->completeOrder(
            $shopthruOrderId,
            $this->createConfirmOrderRequest($shopthruOrderId, $transactionId)
        );

        $this->assertSame(OrderImportResponseInterface::IMPORT_ACTION_CREATE, $createResponse->getImportAction());
        $this->assertTrue($createResponse->getImportActionSuccess());
        $this->assertSame(OrderImportResponseInterface::IMPORT_ACTION_CONFIRM, $confirmResponse->getImportAction());
        $this->assertTrue($confirmResponse->getImportActionSuccess());
        $this->assertSame(ImportLogInterface::STATUS_SUCCESS, $confirmResponse->getImportStatus());

        $searchCriteria = $objectManager->get(SearchCriteriaBuilder::class)
            ->addFilter('shopthru_order_id', $shopthruOrderId)
            ->create();

        $logs = $objectManager->get(ImportLogRepositoryInterface::class)->getList($searchCriteria);

        $this->assertCount(1, $logs);
        $importLog = reset($logs);

        $this->assertSame($shopthruOrderId, $importLog->getShopthruOrderId());
        $this->assertSame(ImportLogInterface::STATUS_SUCCESS, $importLog->getStatus());
        $this->assertSame((string)$createResponse->getMagentoOrderId(), $importLog->getMagentoOrderId());
        $this->assertSame((string)$confirmResponse->getMagentoOrderId(), $importLog->getMagentoOrderId());
        $this->assertSame($shopthruOrderId, $importLog->getShopthruData()['order_id']);
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
