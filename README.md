# Shopthru Connector for Magento 2

## Overview

The Shopthru Connector module enables your Magento 2 store to seamlessly integrate with the Shopthru marketplace. This integration allows Shopthru to retrieve your product information and send orders directly to your Magento store, so you can process them alongside your regular orders.

With this connector installed, your products can be sold on the Shopthru marketplace while all order processing, fulfillment, customer communication, and inventory management remains in your familiar Magento admin interface.

## Requirements

- Magento 2.4.x or higher
- PHP 8.1 or higher
- A Shopthru marketplace account

## Installation

### Via Composer (Recommended)

```Composer install is currently not supported.```

1. Open a terminal and navigate to your Magento installation directory
2. Run the following commands:

```bash
composer require shopthru/module-connector
bin/magento module:enable Shopthru_Connector
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
bin/magento cache:clean
```

### Manual Installation

1. Download the module files
2. Create a directory structure in your Magento installation: `app/code/Shopthru/Connector/`
3. Extract the module files to this directory
4. Open a terminal and navigate to your Magento installation directory
5. Run the following commands:

```bash
bin/magento module:enable Shopthru_Connector
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
bin/magento cache:clean
```

## Configuration

After installation, you'll need to configure the module:

1. Log in to your Magento Admin panel
2. Go to **Stores > Configuration > Shopthru > Shopthru Settings**
3. Configure the following options:

### General Configuration

- **Trigger customer email on import**: When enabled, customers will receive order confirmation emails for orders imported from Shopthru (Default: Yes)
- **Decrement stock on import**: When enabled, product stock will be automatically reduced when orders are imported (Default: Yes)
- **Validate stock is available**: When enabled, the connector will check for available stock before importing an order (Default: No)
- **Link to customer if email matches customer record**: When enabled, orders will be linked to existing customer accounts if the email address matches (Default: No)
- **Automatically invoice orders**: When enabled, orders will be automatically invoiced upon import (Default: Yes)
- **Order status for new orders**: Sets the default status for newly imported orders (Default: Processing)
- **Action for cancelled orders**: Determines the action to take when an order cancellation is received from Shopthru. The options are:
- - Update order status (default): Changes the Magento order's status to the one specified in the "Order status for cancelled orders" setting.
- - Delete order: Completely removes the order from Magento.
- **Order status for cancelled orders**: Sets the status to apply to a Magento order when it is cancelled by Shopthru and the cancellation action is set to "Update order status".
- **Intercept admin API calls**: Enables the experimental feature to enhance the native Magento V1/orders/create API endpoint for Shopthru imports. See the "Experimental: Native API Order Import" section for more details.

## Developer Testing

The import process can be tested using the Magento CLI: 

```bash
shopthru:test-order-create
```


## Shopthru Payment Method

The module installs a special "Shopthru" payment method that is used only for imported orders. This payment method is not visible to customers in your store's checkout but allows proper processing of Shopthru orders.

## Viewing Import Logs

The module keeps detailed logs of all orders imported from Shopthru, allowing you to track the progress and troubleshoot any issues:

1. Log in to your Magento Admin panel
2. Go to **Sales > Shopthru > Import Logs**
3. Here you'll see a list of all import attempts with status information

Alternatively, you can view the import logs when viewing an order created from a Shopthru import. The import log will be displayed in the order view page.


The log view provides the following information:

- **Import ID**: Unique identifier for the import attempt
- **Shopthru Order ID**: Original order ID from Shopthru
- **Publisher Ref**: Reference code for the Shopthru publisher
- **Publisher Name**: Name of the Shopthru publisher
- **Status**: Current status of the import (Pending, Success, or Failed)
- **Magento Order #**: The corresponding Magento order number (for successful imports)
- **Imported At**: Date and time when the import was completed
- **Created At**: Date and time when the import was initiated
- **Failed Reason**: If the import failed, displays the reason for failure

You can click on an individual log entry to view detailed information about the import process, including a step-by-step record of events that occurred during the import.

### Clearing Failed Import Logs

To clear all failed import logs, you can use the following command in the Magento CLI:

```bash
shopthru:clear-failed-import-logs
```

## Experimental: Native API Order Import

Module includes an experimental plugin that enhances the out-of-the-box POST /V1/orders/create endpoint. When activated, this plugin adds critical e-commerce functionalities that are missing from the native API call, such as stock management and post-order processing.

#### Activation:
To use this feature, you must:

Enable "Intercept admin API calls" in the module's admin configuration.

Include the parameter shopthru_order=1 in your V1/orders/create API request.

#### Functionality:
* Pre-Validation: Checks product stock availability before creating the order.
* Data Completion: Fills in missing order data, such as setting the customer as a guest if they don't exist.
* Post-Creation Processing: After the order is saved, it can automatically:
* * Decrement product stock.
* * Generate and capture an invoice.
* * Send the order confirmation email to the customer.

URL params can be used to override the default admin configuration for a single API call by passing a st_flags object in your request with boolean values (e.g., "st_flags": {"decrement_stock": false, "auto_invoice": true}).


## Support

If you encounter any issues with the Shopthru Connector, please contact your Shopthru account representative or submit a support ticket through your Shopthru account portal.
