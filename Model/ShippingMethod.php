<?php
/**
 * ShippingMethod Constants
 */
namespace Shopthru\Connector\Model;

class ShippingMethod
{
    public const FLATRATE = 'flatrate_flatrate';
    public const FREESHIPPING = 'freeshipping_freeshipping';
    public const TABLERATE = 'tablerate_bestway';

    /**
     * Get default title for shipping method
     *
     * @param string $method
     * @return string
     */
    public static function getTitle(string $method): string
    {
        switch ($method) {
            case self::FLATRATE:
                return 'Flat Rate';
            case self::FREESHIPPING:
                return 'Free Shipping';
            case self::TABLERATE:
                return 'Best Way';
            default:
                return 'Shipping';
        }
    }

    /**
     * Get methods as options array
     *
     * @return array
     */
    public static function toOptionArray(): array
    {
        return [
            [
                'value' => self::FLATRATE,
                'label' => self::getTitle(self::FLATRATE)
            ],
            [
                'value' => self::FREESHIPPING,
                'label' => self::getTitle(self::FREESHIPPING)
            ],
            [
                'value' => self::TABLERATE,
                'label' => self::getTitle(self::TABLERATE)
            ]
        ];
    }

    /**
     * Get default method if provided method is invalid
     *
     * @param string|null $method
     * @return string
     */
    public static function getDefaultIfInvalid(?string $method): string
    {
        if ($method === null) {
            return self::FLATRATE;
        }

        switch ($method) {
            case self::FLATRATE:
            case self::FREESHIPPING:
            case self::TABLERATE:
                return $method;
            default:
                return self::FLATRATE;
        }
    }
}
