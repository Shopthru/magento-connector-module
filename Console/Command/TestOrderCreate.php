<?php

namespace Shopthru\Connector\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;

use Shopthru\Connector\Api\ImportOrderManagementInterface;
use Shopthru\Connector\Model\OrderImport;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Shopthru\Connector\Model\Config;

class TestOrderCreate extends Command
{
    /**
     * @param ImportOrderManagementInterface $importOrderManagement
     * @param Config $shopthruConfig
     * @param State $state
     */
    protected function __construct(
        private readonly ImportOrderManagementInterface $importOrderManagement,
        private readonly Config $shopthruConfig,
        private readonly State $state,
    ) {
        return parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('shopthru:test-order-create');
        $this->setDescription('Creates a test order to test the import process');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $output->writeln('Starting test order import');

        $data = $this->getTestOrderData();

        $logEntries = $this->importOrderManagement->importOrders($data);
        $output->writeln('Imported ' . count($logEntries) . ' orders');

        foreach ($logEntries as $logEntry) {
            $output->writeln('--------------------------');
            $output->writeln('Import ID: ' . $logEntry->getImportId());
            $output->writeln('Shopthru Order ID: ' . $logEntry->getShopthruOrderId());
            $output->writeln('Status: ' . $logEntry->getStatus());
            $message = $logEntry->getFailedReason() ? 'Failed Reason: ' . $logEntry->getFailedReason() : 'Magento Order ID: ' . $logEntry->getMagentoOrderId();
            $output->writeln($message);
            $output->writeln('--------------------------');
        }
        $output->writeln('Test orders import complete');
        return Cli::RETURN_SUCCESS;
    }

    /**
     * @return array
     */
    private function getTestOrderData()
    {
        return
            [
                new OrderImport([
//                    'order_id' => uniqid("shopthru-test-"),
                    'order_id' => "shopthru-test",
                    'publisher' => [
                        'name' => 'Shopthru Demo',
                        'ref' => 'STDM'
                    ],
                    'primary_short_code' => 'fac242',
                    'primary_sku' => '24-MB01',
                    'status' => 'processing',
                    'currency' => 'GBP',
                    'locale' => 'en-GB',
                    'sub_total' => 30.00,
                    'tax_total' => 4.50,
                    'total_ex_tax' => 22.50,
                    'total_paid' => 27.00,
                    'total' => 27.00,
                    'shipping_total' => 2.00,
                    'purchase_url' => null,
                    'discount_total' => 5.00,
                    'commission_total' => 4.50,
                    'discount_codes_applied' => 'WELCOME10',
                    'checkout_domain' => null,
                    'ext_store_id' => '1',
                    'ext_attributes' => [],
                    'payment_method' => 'shopthru_payment',
                    'payment_transaction_id' => 'tr-987654321',
                    'payment_data' => [],
                    'created_at' => '2025-03-18T10:15:30Z',
                    'shipping_method' => 'flatrate_flatrate',
                    'shipping_title' => 'Flat Rate Shipping',
                    'items' => [
                        [
                            'order_item_id' => 'shopthru-item-67890',
                            'order_id' => 'shopthru-order-12345',
                            'product_id' => 'product-8765',
                            'short_code' => 'fac242',
                            'product_sku' => '24-MB01',
                            'product_name' => 'Joust Duffle Bag',
                            'item_description' => 'Lightweight, durable travel companion',
                            'external_product_id' => '2043',
                            'is_virtual' => 0,
                            'quantity' => 2,
                            'options' => null,
                            'price' => 12.00,
                            'discount_amount' => 5.00,
                            'commission_total' => 4.50,
                            'commission_rate' => 10,
                            'row_total' => 25.00
                        ]
                    ],
                    'customer' => [
                        'name' => 'John Smith',
                        'email' => 'john.smith@example.com',
                        'telephone' => '555-123-4567',
                        'billing_address' => [
                            'street_address' => [
                                '123 Main St',
                                'Apt 4B'
                            ],
                            'city' => 'New York',
                            'postcode' => '10001',
                            'region' => 'NY',
                            'country' => 'US'
                        ],
                        'shipping_address' => [
                            'street_address' => [
                                '123 Main St',
                                'Apt 4B'
                            ],
                            'city' => 'New York',
                            'postcode' => '10001',
                            'region' => 'NY',
                            'country' => 'US'
                        ]
                    ]
                ])
            ];
    }
}
