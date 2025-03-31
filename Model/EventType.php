<?php
/**
 * EventType Constants
 */
namespace Shopthru\Connector\Model;

class EventType
{
    // Import process events
    const IMPORT_STARTED = 'import:started';
    const IMPORT_DUPLICATE = 'import:duplicate';
    const IMPORT_COMPLETED = 'import:completed';
    const IMPORT_ERROR = 'import:error';

    // Stock-related events
    const STOCK_VALIDATION_SKIPPED = 'stock:validation:skipped';
    const STOCK_VALIDATION_STARTED = 'stock:validation:started';
    const STOCK_VALIDATION_SUCCESS = 'stock:validation:success';
    const STOCK_VALIDATION_FAILED = 'stock:validation:failed';
    const STOCK_VALIDATION_ERROR = 'stock:validation:error';
    const STOCK_INSUFFICIENT = 'stock:insufficient';
    const STOCK_DECREMENTING = 'stock:decrementing';
    const STOCK_DECREMENTED = 'stock:decremented';
    const STOCK_DECREMENT_ERROR = 'stock:decrement:error';

    // Customer-related events
    const CUSTOMER_PREPARING = 'customer:preparing';
    const CUSTOMER_FOUND = 'customer:found';
    const CUSTOMER_GUEST = 'customer:guest';
    const CUSTOMER_ERROR = 'customer:error';

    // Quote-related events
    const QUOTE_CREATING = 'quote:creating';
    const QUOTE_CREATED = 'quote:created';
    const QUOTE_CUSTOMER = 'quote:customer';
    const QUOTE_GUEST = 'quote:guest';
    const QUOTE_ITEMS_ADDING = 'quote:items:adding';
    const QUOTE_ITEM_ADDING = 'quote:item:adding';
    const QUOTE_ITEM_CUSTOM_PRICE = 'quote:item:customPrice';
    const QUOTE_ITEM_ADDED = 'quote:item:added';
    const QUOTE_ITEM_ERROR = 'quote:item:error';
    const QUOTE_ITEMS_ADDED = 'quote:items:added';
    const QUOTE_ADDRESS_BILLING = 'quote:address:billing';
    const QUOTE_ADDRESS_BILLING_SET = 'quote:address:billing:set';
    const QUOTE_ADDRESS_SHIPPING = 'quote:address:shipping';
    const QUOTE_ADDRESS_SHIPPING_SET = 'quote:address:shipping:set';
    const QUOTE_PAYMENT = 'quote:payment';
    const QUOTE_PAYMENT_TRANSACTION = 'quote:payment:transaction';
    const QUOTE_PAYMENT_SET = 'quote:payment:set';
    const QUOTE_SHIPPING_METHOD = 'quote:shipping:method';
    const QUOTE_SHIPPING_METHOD_SET = 'quote:shipping:method:set';
    const QUOTE_FINALIZED = 'quote:finalized';

    // Order-related events
    const ORDER_CREATING = 'order:creating';
    const ORDER_CREATED = 'order:created';
    const ORDER_STATUS = 'order:status';
    const ORDER_SAVED = 'order:saved';

    // Invoice-related events
    const INVOICE_CREATING = 'invoice:creating';
    const INVOICE_CREATED = 'invoice:created';
    const INVOICE_SKIPPED = 'invoice:skipped';
    const INVOICE_ERROR = 'invoice:error';

    // Email-related events
    const EMAIL_SENDING = 'email:sending';
    const EMAIL_SENT = 'email:sent';
    const EMAIL_ERROR = 'email:error';

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
