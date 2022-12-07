<?php
/* --------------------------------------------
 * Key constants.
 *
 * @category   Payment Module
 *
 * @link https://www.payrexx.com
 *
 * @author Payrexx <support@payrexx.com>
 *
 * @copyright  2021 Payrexx
 *
 * @license MIT License
---------      -----------------------------*/

    define('MODULE_PAYMENT_PAYREXX_TEXT_DESCRIPTION', 'Payrexx ermöglicht Ihnen eine sichere und einfache Abwicklung weltweiter Kundenzahlungen mittels Visa, Mastercard, PayPal, Twint, Apple Pay, Google Pay, Sepa, Postfinance und 200+ weitere Zahlungsmittel.<br/> Wenn Sie Fragen haben, senden Sie uns bitte eine E-Mail an <a href="mailto:integrations@payrexx.com">integrations@payrexx.com</a> Oder besuchen <a href="https://www.payrexx.com">https://www.payrexx.com</a><br><br>');
    define('MODULE_PAYMENT_PAYREXX_TEXT_TITLE', 'Payrexx Payment Gateway');
    define('MODULE_PAYMENT_PAYREXX_TEXT_INFO', 'The Payrexx payment gateway accept many different payment methods securely.');
    define('MODULE_PAYMENT_PAYREXX_STATUS_TITLE', 'Enable/Disable Payrexx Module');
    define('MODULE_PAYMENT_PAYREXX_STATUS_DESC', 'Do you want to accept payment by Payrexx?');
    define('MODULE_PAYMENT_PAYREXX_ORDER_STATUS_ID_TITLE', 'Set Order Status');
    define('MODULE_PAYMENT_PAYREXX_ORDER_STATUS_ID_DESC', 'Set the status of orders using this payment module to this value');
    define('MODULE_PAYMENT_PAYREXX_SORT_ORDER_TITLE', 'Display Sort Order');
    define('MODULE_PAYMENT_PAYREXX_SORT_ORDER_DESC', 'Display sort order; the lowest value is displayed first.');
    define('MODULE_PAYMENT_PAYREXX_ZONE_TITLE', 'Payment Zone');
    define('MODULE_PAYMENT_PAYREXX_ZONE_DESC', 'When a zone is selected, this payment method will be enabled for that zone only.');
    define('MODULE_PAYMENT_PAYREXX_ALLOWED_TITLE', 'Allowed Zones');
    define('MODULE_PAYMENT_PAYREXX_ALLOWED_DESC', 'Please enter the zones <b>individually</b> that should be allowed to use this module (e.g. US, UK (leave blank to allow all zones))');
    define('MODULE_PAYMENT_PAYREXX_MIN_ORDER_TITLE', 'Minimum Orders');
    define('MODULE_PAYMENT_PAYREXX_MIN_ORDER_DESC', 'Minimum number of orders for a customer to view this option.');
    define('MODULE_PAYMENT_PAYREXX_INSTANCE_NAME_TITLE', 'Instance Name');
    define('MODULE_PAYMENT_PAYREXX_INSTANCE_NAME_DESC', 'Enter the instance name here. The instance name is part of your Payrexx-url (INSTANCENAME.payrexx.com).');
    define('MODULE_PAYMENT_PAYREXX_API_KEY_TITLE', 'API Key');
    define('MODULE_PAYMENT_PAYREXX_API_KEY_DESC', 'Paste here your API key from the Integrations page of your Payrexx merchant backend.');
    define('MODULE_PAYMENT_PAYREXX_PREFIX_TITLE', 'Prefix');
    define('MODULE_PAYMENT_PAYREXX_PREFIX_DESC', 'This is necessary when you use more than one shop with only one Payrexx account.');
    define('MODULE_PAYMENT_PAYREXX_USE_MODAL_TITLE', 'Use Modal');
    define('MODULE_PAYMENT_PAYREXX_USE_MODAL_DESC', 'Do you want to open Payrexx payment option in Modal?');
    define('MODULE_PAYMENT_PAYREXX_LOOK_AND_FEEL_ID_TITLE', 'Look&Feel Profile ID');
    define('MODULE_PAYMENT_PAYREXX_LOOK_AND_FEEL_ID_DESC', 'Enter a profile ID if you wish to use a specific Look&Feel profile.');
    define('MODULE_PAYMENT_PAYREXX_FAILED', 'Payment failed! Please try again.');
    define('MODULE_PAYMENT_PAYREXX_CANCEL', 'Payment cancelled! Please choose Payrexx and try again.');
    define('MODULE_PAYMENT_PAYREXX_METHOD_DESC', 'Do you want to accept payment by');
    define('MODULE_PAYMENT_PAYREXX_PLATFORM_TITLE', 'Platform');
    $platforms = '<li>payrexx.com</li><li>zahls.ch</li><li>spenden-grunliberale.ch</li><li>deinshop.online</li><li>swissbrain-pay.ch</li><li>loop-pay.com</li><li>shop-and-pay.com</li><li>ideal-pay.ch</li><li>payzzter.com</li>';
    define('MODULE_PAYMENT_PAYREXX_PLATFORM_DESC', 'Choose the platform provider from the list:' . $platforms);

    define('MODULE_PAYMENT_PAYREXX_CHECKOUT_NAME_TITLE', 'Module Title');
    define('MODULE_PAYMENT_PAYREXX_CHECKOUT_NAME_DESC', 'This controls the Module Title on the checkout page');
    define('MODULE_PAYMENT_PAYREXX_CHECKOUT_DESCRIPTION_TITLE', 'Module Desciption');
    define('MODULE_PAYMENT_PAYREXX_CHECKOUT_DESCRIPTION_DESC', 'This controls the Module Description on the checkout page');
