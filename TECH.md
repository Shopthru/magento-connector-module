## Import Process Flow


### Order placed on Shopthru (initial payment validation)
1. Order received from Shopthru via API
2. Order data validated and processed
   1. Validate OrderId has not been imported before
   2. Validate stock levels (Configurable)
3. Magento order created with status "Pending Payment [pending_payment]"
   1. Link to customer if email matches customer record (Configurable)
   2. Order created

### Order confirmed on Shopthru (payment placed successfully)
1. Order confirmed from Shopthru via API
2. Order invoiced (Configurable)
3. Order status updated to "Processing" (Configurable)
4. Save Payment details
5. Send Order confirmation email (Configurable)
6. Decrement Stock (Configurable)

### Order cancelled on Shopthru
1. Order cancelled from Shopthru via API
2. Order status updated to "Cancelled" (Configurable) OR Order deleted (Configurable)
