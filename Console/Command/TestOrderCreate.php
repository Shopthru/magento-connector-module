<?php

namespace Shopthru\Connector\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Catalog\Api\ProductRepositoryInterface;

use Shopthru\Connector\Api\ImportOrderManagementInterface;
use Shopthru\Connector\Model\ConfirmOrderRequest;
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
        private readonly ProductRepositoryInterface $productRepository,
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

        $response = $this->importOrderManagement->importOrder($data);
        $logEntry = $response->getImportLog();

        $output->writeln('--------------------------');
        $output->writeln('Import ID: ' . $logEntry->getImportId());
        $output->writeln('Import Action: ' . $response->getImportAction());
        $output->writeln('Shopthru Order ID: ' . $logEntry->getShopthruOrderId());
        $output->writeln('Status: ' . $response->getImportStatus());
        $message = $logEntry->getFailedReason() ? 'Failed Reason: ' . $logEntry->getFailedReason() : 'Magento Order ID: ' . $logEntry->getMagentoOrderId();
        if ($magentoOrder = $logEntry->getMagentoOrder(false)) {
            $message = 'Magento Order Ref: ' . $magentoOrder->getIncrementId();
        }
        $output->writeln($message);


        $output->writeln('Test order created.');
        $output->writeln('Confirming order...');

        $confirmOrderData = new ConfirmOrderRequest(
            [
                'order_id' => $data->getOrderId(),
                'payment_data' => [
                    'transaction_id' => 'tr-123456789',
                    'cc_last4' => '1234'
                ],
                'order_data' => $data->toArray()
            ]
        );

        $response = $this->importOrderManagement->completeOrder($data->getOrderId(), $confirmOrderData);
        $output->writeln('Test order confirmed.');
        $output->writeln('Import Action: ' . $response->getImportAction());
        $output->writeln('Status: ' . $response->getImportStatus());

        $output->writeln('--------------------------');
        $output->writeln('Log Data:');

        $logEntry = $response->getImportLog();
        foreach ($logEntry->getLogData() as $logData) {
            $logTime = $logData['datetime'] ?? '';
            $logEvent = $logData['event'] ?? '';
            $logDescription = $logData['description'] ?? '';
            $logDataStr = "[{$logTime}] {$logEvent}: {$logDescription}";
            $output->writeln($logDataStr);
        }

        $output->writeln('--------------------------');

        $output->writeln('Test complete');
        return Cli::RETURN_SUCCESS;
    }

    /**
     * @return OrderImport
     */
    private function getTestOrderData()
    {
        $sku = $this->shopthruConfig->getTestOrderSku();

        if (!$sku) {
            throw new \Exception('Test order SKU not set in module configuration');
        }
        //Load product by sku
        $productModel = $this->productRepository->get($sku);
        $productId = $productModel->getId();

        $shopthruOrderId = "shopthru-test-".uniqid();

        return new OrderImport([
                    'order_id' => $shopthruOrderId,
                    'publisher' => [
                        'name' => 'Shopthru Demo',
                        'ref' => 'STDM'
                    ],
                    'primary_short_code' => 'fac242',
                    'primary_sku' => $sku,
                    'status' => 'pending',
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
                    'created_at' => '2025-03-18T10:15:30Z',
                    'shipping_method' => 'flatrate_flatrate',
                    'shipping_title' => 'Flat Rate Shipping',
                    'items' => [
                        [
                            'order_item_id' => 'shopthru-item-67890',
                            'order_id' => $shopthruOrderId,
                            'product_id' => 'product-8765',
                            'short_code' => 'fac242',
                            'product_sku' => $sku,
                            'product_name' => $productModel->getName(),
                            'item_description' => $productModel->getName(),
                            'external_product_id' => $productModel->getId(),
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
                        'name' => 'Mark Wallman',
                        'email' => 'mark@shopthru.com',
                        'telephone' => '07569856854',
                        'billing_address' => [
                            'street_address' => [
                                'Severn House',
                                '20 Middle Street',
                            ],
                            'city' => 'Brighton',
                            'postcode' => 'BN11AL',
                            'country' => 'GB'
                        ],
                        'shipping_address' => [
                            'street_address' => [
                                'Severn House',
                                '20 Middle Street',
                            ],
                            'city' => 'Brighton',
                            'postcode' => 'BN11AL',
                            'country' => 'GB'
                        ]
                    ]
                ]);
    }
}
