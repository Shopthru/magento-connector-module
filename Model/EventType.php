<?php
/**
 * EventType Constants
 */
namespace Shopthru\Connector\Model;

class EventType
{
    public const ORDER_CREATING_DIRECT = 'order_creating_direct';
    public const ORDER_CREATED_DIRECT = 'order_created_direct';
    public const ORDER_ITEM_ADDED = 'order_item_added';
    public const ORDER_ITEM_ERROR = 'order_item_error';
    public const ORDER_SUBTOTAL_ADJUSTED = 'order_subtotal_adjusted';

    // Import process events
    public const IMPORT_STARTED = 'import:started';
    public const IMPORT_DUPLICATE = 'import:duplicate';
    public const IMPORT_COMPLETED = 'import:completed';
    public const IMPORT_ERROR = 'import:error';

    public const ORDER_COMPLETION_STARTED = 'order:completion:started';
    public const ORDER_COMPLETION_COMPLETED = 'order:completion:completed';
    public const ORDER_COMPLETION_ERROR = 'order:completion:error';

    public const ORDER_CANCELLATION_STARTED = 'order:cancellation:started';
    public const ORDER_CANCELLATION_COMPLETED = 'order:cancellation:completed';
    public const ORDER_DELETED = 'order:deleted';

    // Stock-related events
    public const STOCK_VALIDATION_SKIPPED = 'stock:validation:skipped';
    public const STOCK_VALIDATION_STARTED = 'stock:validation:started';
    public const STOCK_VALIDATION_SUCCESS = 'stock:validation:success';
    public const STOCK_VALIDATION_FAILED = 'stock:validation:failed';
    public const STOCK_VALIDATION_ERROR = 'stock:validation:error';
    public const STOCK_INSUFFICIENT = 'stock:insufficient';
    public const STOCK_DECREMENTING = 'stock:decrementing';
    public const STOCK_DECREMENTED = 'stock:decremented';
    public const STOCK_DECREMENT_ERROR = 'stock:decrement:error';

    // Customer-related events
    public const CUSTOMER_PREPARING = 'customer:preparing';
    public const CUSTOMER_FOUND = 'customer:found';
    public const CUSTOMER_GUEST = 'customer:guest';
    public const CUSTOMER_ERROR = 'customer:error';

    // Quote-related events
    public const QUOTE_CREATING = 'quote:creating';
    public const QUOTE_CREATED = 'quote:created';
    public const QUOTE_CUSTOMER = 'quote:customer';
    public const QUOTE_GUEST = 'quote:guest';
    public const QUOTE_ITEMS_ADDING = 'quote:items:adding';
    public const QUOTE_ITEM_ADDING = 'quote:item:adding';

    public const QUOTE_ITEM_DISCOUNT = 'quote:item:discount';
    public const QUOTE_ITEM_CUSTOM_PRICE = 'quote:item:customPrice';
    public const QUOTE_ITEM_ADDED = 'quote:item:added';
    public const QUOTE_ITEM_ERROR = 'quote:item:error';
    public const QUOTE_ITEMS_ADDED = 'quote:items:added';
    public const QUOTE_ADDRESS_BILLING = 'quote:address:billing';
    public const QUOTE_ADDRESS_BILLING_SET = 'quote:address:billing:set';
    public const QUOTE_ADDRESS_SHIPPING = 'quote:address:shipping';
    public const QUOTE_ADDRESS_SHIPPING_SET = 'quote:address:shipping:set';
    public const QUOTE_PAYMENT = 'quote:payment';
    public const QUOTE_PAYMENT_TRANSACTION = 'quote:payment:transaction';
    public const QUOTE_PAYMENT_SET = 'quote:payment:set';
    public const QUOTE_SHIPPING_METHOD = 'quote:shipping:method';
    public const QUOTE_SHIPPING_METHOD_SET = 'quote:shipping:method:set';
    public const QUOTE_FINALIZED = 'quote:finalized';

    public const QUOTE_ENFORCING_TOTALS = 'quote:enforcing:totals';
    public const QUOTE_TOTAL_DIFFERENCE = 'quote:total:difference';
    public const QUOTE_TOTALS_ENFORCED = 'quote:total:enforced';
    public const QUOTE_DISCOUNT_ENFORCED = 'quote:discount:enforced';
    public const ORDER_SHIPPING_CORRECTED = 'order:shipping:corrected';

    // Order-related events
    public const ORDER_CREATING = 'order:creating';
    public const ORDER_CREATED = 'order:created';
    public const ORDER_STATUS = 'order:status';
    public const ORDER_SAVED = 'order:saved';

    // Invoice-related events
    public const INVOICE_CREATING = 'invoice:creating';
    public const INVOICE_CREATED = 'invoice:created';
    public const INVOICE_SKIPPED = 'invoice:skipped';
    public const INVOICE_ERROR = 'invoice:error';

    // Email-related events
    public const EMAIL_SENDING = 'email:sending';
    public const EMAIL_SENT = 'email:sent';
    public const EMAIL_ERROR = 'email:error';
    public const EMAIL_SKIPPED = 'email:skipped';

    /**
     * Get group for event type
     *
     * @param string $eventType
     * @return string
     */
    public static function getGroup(string $eventType): string
    {
        if (strpos($eventType, 'import:') === 0) {
            return 'Import';
        }
        if (strpos($eventType, 'stock:') === 0) {
            return 'Stock';
        }
        if (strpos($eventType, 'customer:') === 0) {
            return 'Customer';
        }
        if (strpos($eventType, 'quote:') === 0) {
            return 'Quote';
        }
        if (strpos($eventType, 'order:') === 0) {
            return 'Order';
        }
        if (strpos($eventType, 'invoice:') === 0) {
            return 'Invoice';
        }
        if (strpos($eventType, 'email:') === 0) {
            return 'Email';
        }
        return 'Other';
    }

    /**
     * Is this an error event?
     *
     * @param string $eventType
     * @return bool
     */
    public static function isError(string $eventType): bool
    {
        return strpos($eventType, ':error') !== false || $eventType === self::IMPORT_ERROR;
    }
}
