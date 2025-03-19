<?php
/**
 * PaymentMethodAvailable.php
 */
namespace Shopthru\Connector\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Shopthru\Connector\Model\Payment\Method\Shopthru;

class PaymentMethodAvailable implements ObserverInterface
{
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
        if ($method->getCode() === Shopthru::METHOD_CODE && !$observer->getEvent()->getIsAdmin()) {
            $result->setData('is_available', false);
        }
    }
}
