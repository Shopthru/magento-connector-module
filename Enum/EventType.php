<?php
/**
 * Event Type Enum
 */
namespace Shopthru\Connector\Enum;

enum EventType: string
{
    // Import process events
    case IMPORT_STARTED = 'import:started';
    case IMPORT_DUPLICATE = 'import:duplicate';
    case IMPORT_COMPLETED = 'import:completed';
    case IMPORT_ERROR = 'import:error';

    // Stock-related events
    case STOCK_VALIDATION_SKIPPED = 'stock:validation:skipped';
    case STOCK_VALIDATION_STARTED = 'stock:validation:started';
    case STOCK_VALIDATION_SUCCESS = 'stock:validation:success';
    case STOCK_VALIDATION_FAILED = 'stock:validation:failed';
    case STOCK_VALIDATION_ERROR = 'stock:validation:error';
    case STOCK_INSUFFICIENT = 'stock:insufficient';
    case STOCK_DECREMENTING = 'stock:decrementing';
    case STOCK_DECREMENTED = 'stock:decremented';
    case STOCK_DECREMENT_ERROR = 'stock:decrement:error';

    // Customer-related events
    case CUSTOMER_PREPARING = 'customer:preparing';
    case CUSTOMER_FOUND = 'customer:found';
    case CUSTOMER_GUEST = 'customer:guest';
    case CUSTOMER_ERROR = 'customer:error';

    // Quote-related events
    case QUOTE_CREATING = 'quote:creating';
    case QUOTE_CREATED = 'quote:created';
    case QUOTE_CUSTOMER = 'quote:customer';
    case QUOTE_GUEST = 'quote:guest';
    case QUOTE_ITEMS_ADDING = 'quote:items:adding';
    case QUOTE_ITEM_ADDING = 'quote:item:adding';
    case QUOTE_ITEM_CUSTOM_PRICE = 'quote:item:customPrice';
    case QUOTE_ITEM_ADDED = 'quote:item:added';
    case QUOTE_ITEM_ERROR = 'quote:item:error';
    case QUOTE_ITEMS_ADDED = 'quote:items:added';
    case QUOTE_ADDRESS_BILLING = 'quote:address:billing';
    case QUOTE_ADDRESS_BILLING_SET = 'quote:address:billing:set';
    case QUOTE_ADDRESS_SHIPPING = 'quote:address:shipping';
    case QUOTE_ADDRESS_SHIPPING_SET = 'quote:address:shipping:set';
    case QUOTE_PAYMENT = 'quote:payment';
    case QUOTE_PAYMENT_TRANSACTION = 'quote:payment:transaction';
    case QUOTE_PAYMENT_SET = 'quote:payment:set';
    case QUOTE_SHIPPING_METHOD = 'quote:shipping:method';
    case QUOTE_SHIPPING_METHOD_SET = 'quote:shipping:method:set';
    case QUOTE_FINALIZED = 'quote:finalized';

    // Order-related events
    case ORDER_CREATING = 'order:creating';
    case ORDER_CREATED = 'order:created';
    case ORDER_STATUS = 'order:status';
    case ORDER_SAVED = 'order:saved';

    // Invoice-related events
    case INVOICE_CREATING = 'invoice:creating';
    case INVOICE_CREATED = 'invoice:created';
    case INVOICE_SKIPPED = 'invoice:skipped';
    case INVOICE_ERROR = 'invoice:error';

    // Email-related events
    case EMAIL_SENDING = 'email:sending';
    case EMAIL_SENT = 'email:sent';
    case EMAIL_ERROR = 'email:error';

    /**
     * Get group for event type
     *
     * @return string
     */
    public function getGroup(): string
    {
        return match (true) {
            str_starts_with($this->value, 'import:') => 'Import',
            str_starts_with($this->value, 'stock:') => 'Stock',
            str_starts_with($this->value, 'customer:') => 'Customer',
            str_starts_with($this->value, 'quote:') => 'Quote',
            str_starts_with($this->value, 'order:') => 'Order',
            str_starts_with($this->value, 'invoice:') => 'Invoice',
            str_starts_with($this->value, 'email:') => 'Email',
            default => 'Other',
        };
    }

    /**
     * Is this an error event?
     *
     * @return bool
     */
    public function isError(): bool
    {
        return str_contains($this->value, ':error') || $this === self::IMPORT_ERROR;
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

        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        return null;
    }
}
