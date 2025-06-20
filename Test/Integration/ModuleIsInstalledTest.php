<?php
declare(strict_types=1);
namespace Shopthru\Connector\Test\Integration;

use Magento\Framework\Module\ModuleListInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Shopthru\Connector\Model\ImportLog;
use Shopthru\Connector\Model\ImportOrderManagement;
use Shopthru\Connector\Model\ImportProcessors\DirectOrderCreator;
use Shopthru\Connector\Model\Payment\Method\Shopthru;
use Shopthru\Connector\Model\ResourceModel\ImportLog\Grid\Collection;

class ModuleIsInstalledTest extends TestCase
{
    public function testModuleIsInstalled()
    {
        $moduleNames = Bootstrap::getObjectManager()->get(ModuleListInterface::class)->getNames();
        $this->assertContains('Shopthru_Connector', $moduleNames);
    }

    public function testSomeClassesInstantiate()
    {
        $objectManager = Bootstrap::getObjectManager();

        $this->assertInstanceOf(ImportLog::class, $objectManager->get(ImportLog::class));
        $this->assertInstanceOf(ImportOrderManagement::class, $objectManager->get(ImportOrderManagement::class));
        $this->assertInstanceOf(DirectOrderCreator::class, $objectManager->get(DirectOrderCreator::class));
        $this->assertInstanceOf(Shopthru::class, $objectManager->get(Shopthru::class));
        $this->assertInstanceOf(Collection::class, $objectManager->get(Collection::class));
    }
}
