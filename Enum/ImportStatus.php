<?php
/**
 * Import Status Enum
 */
namespace Shopthru\Connector\Enum;

enum ImportStatus: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';

    /**
     * Get readable label for status
     *
     * @return string
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::SUCCESS => 'Success',
            self::FAILED => 'Failed',
        };
    }

    /**
     * Get CSS class for status
     *
     * @return string
     */
    public function getCssClass(): string
    {
        return match($this) {
            self::PENDING => 'grid-severity-minor',
            self::SUCCESS => 'grid-severity-notice',
            self::FAILED => 'grid-severity-critical',
        };
    }

    /**
     * Create from string value
     *
     * @param string|null $value
     * @return self|null
     */
    public static function fromValue(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        return match($value) {
            'pending' => self::PENDING,
            'success' => self::SUCCESS,
            'failed' => self::FAILED,
            default => null,
        };
    }
}
