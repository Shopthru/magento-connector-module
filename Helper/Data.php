<?php
namespace Shopthru\Connector\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * Extract first name from full name
     *
     * @param string $name
     * @return string
     */
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

}
