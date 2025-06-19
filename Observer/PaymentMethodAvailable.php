<?php
/**
 * PaymentMethodAvailable.php
 */
namespace Shopthru\Connector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Shopthru\Connector\Model\ImportOrderContext;
use Shopthru\Connector\Model\Payment\Method\Shopthru;

class PaymentMethodAvailable implements ObserverInterface
{
    /**
     * @param ImportOrderContext $importOrderContext
     */
    public function __construct(private ImportOrderContext $importOrderContext)
    {}

    /**
     * Make sure Shopthru payment method is only available for admin
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $method = $observer->getEvent()->getMethodInstance();
        $result = $observer->getEvent()->getResult();

        // Disable Shopthru payment method in frontend
        if ($method->getCode() === Shopthru::CODE
            && !$this->importOrderContext->getIsShopthruImport()
        ) {
            $result->setData('is_available', false);
        }
    }
}
