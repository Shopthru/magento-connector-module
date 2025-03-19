<?php
/**
 * Shipping Method Enum
 */
namespace Shopthru\Connector\Enum;

enum ShippingMethod: string
{
    case FLATRATE = 'flatrate_flatrate';
    case FREESHIPPING = 'freeshipping_freeshipping';
    case TABLERATE = 'tablerate_bestway';

    /**
     * Get default title for shipping method
     *
     * @return string
     */
    public function getTitle(): string
    {
        return match($this) {
            self::FLATRATE => 'Flat Rate',
            self::FREESHIPPING => 'Free Shipping',
            self::TABLERATE => 'Best Way',
        };
    }

    /**
     * Get methods as options array
     *
     * @return array
     */
    public static function toOptionArray(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[] = [
                'value' => $case->value,
                'label' => $case->getTitle()
            ];
        }
        return $options;
    }

    /**
     * Create from string value
     *
     * @param string|null $value
     * @return self
     */
    public static function fromValue(?string $value): self
    {
        if ($value === null) {
            return self::FLATRATE;
        }

        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        return self::FLATRATE;
    }
}
