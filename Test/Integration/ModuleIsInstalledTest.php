<?php
declare(strict_types=1);
namespace Shopthru\Connector\Test\Integration;

use Magento\Framework\Module\ModuleListInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Shopthru\Connector\Api\Data\CancelOrderRequestInterface;
use Shopthru\Connector\Api\Data\ConfirmOrderRequestInterface;
use Shopthru\Connector\Api\Data\ImportLogInterface;
use Shopthru\Connector\Api\Data\OrderImportResponseInterface;
use Shopthru\Connector\Block\Adminhtml\Order\View\Tab\ImportLog as ImportLogTab;
use Shopthru\Connector\Block\Payment\Info\ShopthruPaymentInfo;
use Shopthru\Connector\Console\Command\TestOrderCreate;
use Shopthru\Connector\Model\ConfirmOrderRequest;
use Shopthru\Connector\Model\ImportLog;
use Shopthru\Connector\Model\ImportOrderManagement;
use Shopthru\Connector\Model\ImportProcessors\DirectOrderCreator;
use Shopthru\Connector\Model\ImportProcessors\DirectOrderCreator\DefaultData;
use Shopthru\Connector\Model\OrderImportResponse;
use Shopthru\Connector\Model\Payment\Method\Shopthru;
use Shopthru\Connector\Model\ResourceModel\ImportLog\Grid\Collection;
use Shopthru\Connector\Model\Service\ClearFailedImportLogs;
use Shopthru\Connector\Observer\PaymentMethodAvailable;
use Shopthru\Connector\Plugin\Rest\OrderRepositoryPlugin;

class ModuleIsInstalledTest extends TestCase
{
    public function testModuleIsInstalled()
    {
        $moduleNames = Bootstrap::getObjectManager()->get(ModuleListInterface::class)->getNames();
        $this->assertContains('Shopthru_Connector', $moduleNames);
    }

    public function testCodeStyleCorrectedClassesInstantiate()
    {
        $objectManager = Bootstrap::getObjectManager();

        $this->assertInstanceOf(ImportLog::class, $objectManager->get(ImportLog::class));
        $this->assertInstanceOf(ImportOrderManagement::class, $objectManager->get(ImportOrderManagement::class));
        $this->assertInstanceOf(DirectOrderCreator::class, $objectManager->get(DirectOrderCreator::class));
        $this->assertInstanceOf(Shopthru::class, $objectManager->get(Shopthru::class));
        $this->assertInstanceOf(Collection::class, $objectManager->get(Collection::class));
        $this->assertInstanceOf(CancelOrderRequestInterface::class, $objectManager->get(CancelOrderRequestInterface::class));
        $this->assertInstanceOf(ConfirmOrderRequestInterface::class, $objectManager->get(ConfirmOrderRequestInterface::class));
        $this->assertInstanceOf(ImportLogInterface::class, $objectManager->get(ImportLogInterface::class));
        $this->assertInstanceOf(OrderImportResponseInterface::class, $objectManager->get(OrderImportResponseInterface::class));
        $this->assertInstanceOf(ImportLogTab::class, $objectManager->get(ImportLogTab::class));
        $this->assertInstanceOf(ShopthruPaymentInfo::class, $objectManager->get(ShopthruPaymentInfo::class));
        $this->assertInstanceOf(TestOrderCreate::class, $objectManager->get(TestOrderCreate::class));
        $this->assertInstanceOf(ConfirmOrderRequest::class, $objectManager->get(ConfirmOrderRequest::class));
        $this->assertInstanceOf(DefaultData::class, $objectManager->get(DefaultData::class));
        $this->assertInstanceOf(OrderImportResponse::class, $objectManager->get(OrderImportResponse::class));
        $this->assertInstanceOf(ClearFailedImportLogs::class, $objectManager->get(ClearFailedImportLogs::class));
        $this->assertInstanceOf(PaymentMethodAvailable::class, $objectManager->get(PaymentMethodAvailable::class));
        $this->assertInstanceOf(OrderRepositoryPlugin::class, $objectManager->get(OrderRepositoryPlugin::class));
    }
}
