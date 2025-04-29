<?php

namespace Shopthru\Connector\Block\Payment\Info;

use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;
use Shopthru\Connector\Model\Payment\Method\Shopthru;

class ShopthruPaymentInfo extends \Magento\Payment\Block\ConfigurableInfo
{
    /**
     * @param Context $context
     * @param ConfigInterface $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
        array $data = [])
    {
        $data['methodCode'] = Shopthru::CODE;
        parent::__construct($context, $config, $data);
    }

    protected function getLabel($field): Phrase
    {
        switch ($field) {
            case 'payment_method':
                return __('Payment Method');
            case 'transaction_id':
                return __('Transaction ID');
            case 'payment_data':
                return __('Payment Data');
            case strpos($field, 'payment_data') === 0:
                $field = str_replace('payment_data_', '', $field);
                return __('Payment Data #' . ucfirst($field));
        }

        return __($field);
    }

    protected function getValueView($field, $value)
    {
        if (is_array($value)) {
            // return a key value string
            return implode(PHP_EOL, array_map(function ($key, $value) {
                return $key . ': ' . $value;
            }, array_keys($value), $value));
        }
        return parent::getValueView($field, $value);
    }

}
