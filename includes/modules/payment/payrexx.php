<?php
/* --------------------------------------------
 * Class payrexx_ORIGIN.
 *
 * Payment gateway for Payrexx AG.
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
 *
* VERSION HISTORY:
* 1.0.0 Payrexx Payment Gateway.
---------      -----------------------------*/

use \Payrexx\Models\Response\Transaction;

class payrexx_ORIGIN
{
    public $code, $title, $description, $enabled;
    /**
     * @var string
     */
    public $min_order;
    /**
     * @var string
     */
    public $sort_order;
    /**
     * @var string
     */
    public $info;
    public $order_status;

    /**
     * payrexx_ORIGIN constructor.
     */
    public function __construct()
    {
        $this->code        = 'payrexx';
        $this->title       = defined('MODULE_PAYMENT_PAYREXX_TEXT_TITLE') ? MODULE_PAYMENT_PAYREXX_TEXT_TITLE : 'Payrexx Payment Gateway';
        $this->description = defined('MODULE_PAYMENT_PAYREXX_TEXT_DESCRIPTION') ? MODULE_PAYMENT_PAYREXX_TEXT_DESCRIPTION : '';
        $this->info = defined('MODULE_PAYMENT_PAYREXX_TEXT_INFO') ? MODULE_PAYMENT_PAYREXX_TEXT_INFO : '';
        $this->min_order   = defined('MODULE_PAYMENT_PAYREXX_MIN_ORDER') ? MODULE_PAYMENT_PAYREXX_MIN_ORDER : '0';
        $this->sort_order  = defined('MODULE_PAYMENT_PAYREXX_SORT_ORDER') ? MODULE_PAYMENT_PAYREXX_SORT_ORDER : '0';
        $this->enabled     = defined('MODULE_PAYMENT_' . strtoupper($this->code) . '_STATUS') && filter_var(constant('MODULE_PAYMENT_' . strtoupper($this->code) . '_STATUS'), FILTER_VALIDATE_BOOLEAN);
        $this->register_autoloader();
    }

    /**
     *
     */
    public function update_status()
    {
        global $order;
        if (($this->enabled == true) && ((int)MODULE_PAYMENT_PAYREXX_ZONE > 0)) {
            $check_flag = false;
            $sql        = xtc_db_query("SELECT zone_id FROM " . TABLE_ZONES_TO_GEO_ZONES . " WHERE geo_zone_id = '"
                . MODULE_PAYMENT_PAYREXX_ZONE . "' AND zone_country_id = '"
                . $order->billing['country']['id'] . "' ORDER BY zone_id");

            while ($check = xtc_db_fetch_array($sql)) {
                if ($check['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check['zone_id'] == $order->billing['zone_id']) {
                    $check_flag = true;
                    break;
                }
            }
            if ($check_flag == false) {
                $this->enabled = false;
            }
        }

    }

    /**
     * @return false
     */
    public function javascript_validation()
    {
        return false;
    }

    /**
     * @return bool
     */
    protected function _validateSignature() {
        $payrexx = new \Payrexx\Payrexx(MODULE_PAYMENT_PAYREXX_INSTANCE_NAME, MODULE_PAYMENT_PAYREXX_API_KEY, '', trim(MODULE_PAYMENT_PAYREXX_PLATFORM));
        $signatureCheck = new \Payrexx\Models\Request\SignatureCheck();
        try {
            $response = $payrexx->getOne($signatureCheck);
            return true;
        } catch (\Payrexx\PayrexxException $e) {
            return false;
        }
    }

    public function getGatewayById($id) {
        $payrexx = new \Payrexx\Payrexx(MODULE_PAYMENT_PAYREXX_INSTANCE_NAME, MODULE_PAYMENT_PAYREXX_API_KEY, '', trim(MODULE_PAYMENT_PAYREXX_PLATFORM));
        $gateway = new \Payrexx\Models\Request\Gateway();
        $gateway->setId($id);
        try {
            $response = $payrexx->getOne($gateway);
            return $response;
        } catch (\Payrexx\PayrexxException $e) {
            return [];
        }
    }

    /**
     * @return array|false
     */
    public function selection()
    {
        if (isset($_GET['payrexx_cancel'])) {
            $_SESSION['gm_error_message'] = urlencode(MODULE_PAYMENT_PAYREXX_CANCEL);
        }

        if ($this->_validateSignature()) {
            $selection = [
                'id' => $this->code,
                'module' => constant('MODULE_PAYMENT_PAYREXX_DISPLAY_NAME_' . strtoupper($_SESSION['language_code'])),
                'description' => $this->_getDescription(),
            ];

            return $selection;
        }

        return false;
    }

    /**
     * @return string
     */
    protected function _getDescription()
    {
        $description = constant('MODULE_PAYMENT_PAYREXX_DISPLAY_DESCRIPTION_' . strtoupper($_SESSION['language_code']));
        $description .= '<style> .payrexx .payment-module-icon img{background: initial !important;}</style><br>';
        foreach ($this->getPaymentMethods() as $method) {
            if (constant(MODULE_PAYMENT_PAYREXX_ . strtoupper($method)) === 'true') {
                $description .= $this->_getPaymentMethodIcon($method);
            }
        }

        return $description;
    }

    /**
     * @param $paymentMethod
     *
     * @return string
     */
    protected function _getPaymentMethodIcon($paymentMethod)
    {
        if(file_exists(DIR_FS_CATALOG . 'images/icons/payment/payrexx/card_' . $paymentMethod) . '.svg')
        {
            $src = xtc_href_link("images/icons/payment/payrexx/card_{$paymentMethod}.svg", '', 'SSL', false, false, false, true, true);
            return '<img src="' .$src. '" alt="' . $paymentMethod . '" width="70" style="margin: 7px 7px 7px 0;background: #fff;">';
        }

        return '';
    }

    /**
     * Loads Payrexx PHP Library.
     */
    public function register_autoloader()
    {
        spl_autoload_register(function ($class) {
            $root = SHOP_ROOT . 'system/classes/external';
            $classFile = $root . '/' . str_replace('\\', '/', $class) . '.php';
            if (file_exists($classFile)) {
                require_once $classFile;
            }
        });
    }

    /**
     * @return false
     */
    public function pre_confirmation_check()
    {
        return false;
    }

    /**
     * @return false
     */
    public function confirmation()
    {
        if (isset($_GET['payrexx_failed'])) {
            $_SESSION['gm_error_message'] = urlencode(MODULE_PAYMENT_PAYREXX_FAILED);
            $this->_checkGatewayResponse();
        }

        return false;
    }

    /**
     * @return false
     */
    function process_button()
    {
        return false;
    }

    function payment_action()
    {
        return false;
    }

    /**
     * @return false
     */
    public function before_process()
    {
        if (!isset($_GET['payrexx_success'])) {
            $payrexx = new Payrexx\Payrexx(MODULE_PAYMENT_PAYREXX_INSTANCE_NAME, MODULE_PAYMENT_PAYREXX_API_KEY);
            $response = $payrexx->create($this->_createPayrexxGateway());
            $_SESSION['payrexx_gateway_id'] = $response->getId();
            $_SESSION['payrexx_gateway_referrenceId'] = $_SESSION['cartID'];
            $payrexxPaymentUrl = 'https://' . MODULE_PAYMENT_PAYREXX_INSTANCE_NAME . '.payrexx.com/'
                . $_SESSION['language_code'] . '/?payment=' . $response->getHash();
            xtc_redirect($payrexxPaymentUrl);
        }

        return false;
    }

    /**
     * @return false
     */
    public function after_process()
    {
        try {
            if(isset($_GET['payrexx_success'])) {
                $_SESSION['payrexx_gateway_referrenceId'] = new IdType((int)$GLOBALS['insert_id']);
                $this->_checkGatewayResponse();
            } else {
                $_SESSION['gm_error_message'] = urlencode(MODULE_PAYMENT_PAYREXX_FAILED);
                xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
            }
        } catch (\Payrexx\PayrexxException $e) {
        }

        return false;
    }

    /**
     * Gets Payrexx Gateway.
     *
     * @return \Payrexx\Models\Request\Gateway
     */
    protected function _createPayrexxGateway() {
        global $order;
        $insertId = trim($_SESSION['cartID']); //new IdType((int)$GLOBALS['insert_id']);
        $gateway = new \Payrexx\Models\Request\Gateway();

        /**
         * success and failed url in case that merchant redirects to payment site instead of using the modal view
         */
        $gateway->setSuccessRedirectUrl(xtc_href_link(FILENAME_CHECKOUT_PROCESS, 'payrexx_success=1', 'SSL'));
        $gateway->setFailedRedirectUrl(xtc_href_link(FILENAME_CHECKOUT_CONFIRMATION, 'payrexx_failed=1', 'SSL'));
        $gateway->setCancelRedirectUrl(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, 'payrexx_cancel=1', 'SSL'));

        $amount = floatval($order->info['subtotal']);
        $amount += floatval($_SESSION['CSCC_debug']['shipping_cost']);
        if (!$_SESSION['customers_status']['customers_status_show_price_tax']) {
            $amount += floatval($order->info['tax']);
        }

        // Round to 5 and format as cent
        //$amount = 5 * round(($amount * 100) / 5);;
        $amount = $amount * 100;

        $currency = $order->info['currency'];

        $productNames = array();
        $basket = array();
        foreach ($order->products as $item) {
            $quantity = $item['qty'] > 1 ? $item['quantity'] . 'x ' : '';
            $productNames[] = $quantity . $item['name'];

            $basket[] = [
                'name' => [
                    1 => $item['name']
                ],
                'quantity' => $item['quantity'],
                'amount' => $item['final_price'] * 100
            ];
        }

        //$gateway->setBasket($basket);
        $gateway->setAmount((int)$amount);
        if ($currency == "") {
            $currency = "USD";
        }

        $gateway->setCurrency($currency);

        $gateway->setPurpose(implode(', ', $productNames));
        $gateway->setPsp(array());
        $gateway->setSkipResultPage(true);

        if (empty(MODULE_PAYMENT_PAYREXX_PREFIX)) {
            $gateway->setReferenceId($insertId);
        } else {
            $gateway->setReferenceId(MODULE_PAYMENT_PAYREXX_PREFIX . '_' . $insertId);
        }

        if (!empty(MODULE_PAYMENT_PAYREXX_LOOK_AND_FEEL_ID)) {
            $gateway->setLookAndFeelProfile(MODULE_PAYMENT_PAYREXX_LOOK_AND_FEEL_ID);
        }

        $gateway->addField($type = 'title', $value = '');
        $gateway->addField($type = 'forename', $value = $order->billing['firstname']);
        $gateway->addField($type = 'surname', $value = $order->billing['lastname']);
        $gateway->addField($type = 'company', $value = $order->billing['company']);
        $gateway->addField($type = 'street', $value = $order->billing['street_address']);
        $gateway->addField($type = 'postcode', $value = $order->billing['postc  ode']);
        $gateway->addField($type = 'place', $value = $order->billing['city']);
        $gateway->addField($type = 'country', $value = $order->billing['country']['iso_code_2']);
        $gateway->addField($type = 'phone', $value = $order->customer['telephone']);
        $gateway->addField($type = 'email', $value = $order->customer['email_address']);
        $gateway->addField($type = 'custom_field_1', $value = $insertId, $name = 'Gambio Order ID');

        return $gateway;
    }

    /**
     * Gets transaction and check response.
     */
    protected function _checkGatewayResponse() {
        $insertId = trim($_SESSION['payrexx_gateway_referrenceId']);
        $gateway = $this->getGatewayById($_SESSION['payrexx_gateway_id']);

        if ($gateway && $invoices = $gateway->getInvoices()) {
            $status = $invoices[0]['transactions'][0]['status'];

            /**
             * Order Statuses
             *  0 => Not validated
             *  1 => Pending
             *  2 => Processing
             *  3 => Dispatched
             *  99 => Cancelled
             *  149 => Invoice created
             */
            switch ($status) {
                case Transaction::WAITING:
                case Transaction::AUTHORIZED:
                case Transaction::RESERVED:
                    $this->_updateOrderStatus($insertId, 1);
                    break;
                case Transaction::CONFIRMED:
                    ;
                    $this->_updateOrderStatus($insertId, 2);
                    break;
                case Transaction::CANCELLED:
                case Transaction::ERROR:
                case Transaction::EXPIRED:
                default:
                    $this->_updateOrderStatus($insertId, 99);
                    break;
            }
        }
    }

    protected function _updateOrderStatus($insertId, $statusId) {
        if(!empty($insertId)) {
            xtc_db_query(
                "UPDATE " . TABLE_ORDERS . " SET orders_status={$statusId} WHERE orders_id='{$insertId}'"
            );
        }
    }

    /**
     * @return false
     */
    public function get_error()
    {
        return false;
    }

    /**
     * @return int|mixed|string
     */
    public function check()
    {
        if (!isset($this->_check))
        {
            $check_query  = xtc_db_query("SELECT `value` FROM " . TABLE_CONFIGURATION
                . " WHERE `key` = 'configuration/MODULE_PAYMENT_" . strtoupper($this->code)
                . "_STATUS'");
            $this->_check = xtc_db_num_rows($check_query);
        }

        return $this->_check;
    }

    /**
     * Installs the Module configurations.
     */
    public function install()
    {
        $config     = $this->_configuration();
        $sort_order = 0;
        foreach ($config as $key => $data) {
            $install_query = "INSERT INTO `gx_configurations` ( `key`, `value`, `sort_order`, `type`, `last_modified`) "
                . "values ('configuration/MODULE_PAYMENT_" . strtoupper($this->code) . "_" . $key . "', '"
                . $data['value'] . "', '" . $sort_order . "', '" . addslashes($data['type']) . "', now())";
            xtc_db_query($install_query);
            $sort_order++;
        }
    }

    /**
     * Removes the Module configurations.
     */
    public function remove()
    {
        xtc_db_query("DELETE FROM " . TABLE_CONFIGURATION . " WHERE `key` IN ('" . implode("', '", $this->keys()) . "')");
    }


    /**
     * Determines the module's configuration keys.
     *
     * @return array
     */
    public function keys()
    {
        $ckeys = array_keys($this->_configuration());
        $keys  = [];
        foreach ($ckeys as $k) {
            $keys[] = 'configuration/MODULE_PAYMENT_' . strtoupper($this->code) . '_' . $k;
        }

        return $keys;
    }

    /**
     * @return bool
     */
    public function isInstalled()
    {
        $isInstalled = true;
        foreach($this->keys() as $key)
        {
            if(!defined($key))
            {
                $isInstalled = false;
            }
        }

        return $isInstalled;
    }

    protected function _xtc_get_languages()
    {
        $db = StaticGXCoreLoader::getDatabaseQueryBuilder();
        $languages = $db->select('languages_id, name, code, status, status_admin')
            ->select('languages_id AS id')
            ->order_by('sort_order ASC')
            ->get('languages')
            ->result_array();
        return $languages;
    }

    /**
     * @return string[][]
     */
    public function _configuration()
    {
        $config = [
            'STATUS'     => [
                'value' => 'True',
                'type'  => 'switcher',
            ],
            'SORT_ORDER' => [
                'value' => '-9999',
                'type' => 'number'
            ],
            'ALLOWED'    => [
                'value' => '',
                'type' => 'text'
            ],
            'ZONE'       => [
                'value' => '',
                'type'  => 'geo-zone',
            ],
            'PLATFORM'    => [
                'value'  => 'payrexx.com',
                'type' => 'text'
            ],
            'INSTANCE_NAME'    => [
                'value' => '',
                'type' => 'text'
            ],
            'API_KEY'    => [
                'value' => '',
                'type' => 'text'
            ],
            'PREFIX'    => [
                'value' => 'gambio',
                'type' => 'text'
            ],
            'LOOK_AND_FEEL_ID'    => [
                'value' => '',
                'type' => 'text'
            ]
        ];

        /**
         * Creating text fields for each language.
         */
        $availableLanguages = $this->_xtc_get_languages();
        if(!empty($availableLanguages)) {
            foreach ($availableLanguages as $language) {
                define('MODULE_PAYMENT_PAYREXX_DISPLAY_NAME_' . strtoupper($language['code']) . '_TITLE', MODULE_PAYMENT_PAYREXX_DISPLAY_NAME_TITLE_TXT . ' ' . $language['name']);
                define('MODULE_PAYMENT_PAYREXX_DISPLAY_NAME_' . strtoupper($language['code']) . '_DESC', MODULE_PAYMENT_PAYREXX_DISPLAY_NAME_DESC_TXT . ' ' . $language['name']);
                $config['DISPLAY_NAME_' . strtoupper($language['code'])] = ['value' => $this->title, 'type' => 'text'];

                define('MODULE_PAYMENT_PAYREXX_DISPLAY_DESCRIPTION_' . strtoupper($language['code']) . '_TITLE', MODULE_PAYMENT_PAYREXX_DISPLAY_DESCRIPTION_TITLE_TXT . ' ' . $language['name']);
                define('MODULE_PAYMENT_PAYREXX_DISPLAY_DESCRIPTION_' . strtoupper($language['code']) . '_DESC', MODULE_PAYMENT_PAYREXX_DISPLAY_DESCRIPTION_DESC_TXT . ' ' . $language['name']);
                $config['DISPLAY_DESCRIPTION_' . strtoupper($language['code'])] = ['value' => $this->info, 'type' => 'text'];
            }
        }

        /**
         * Creating checkbox for each payment method.
         */
        foreach ($this->getPaymentMethods() as $method) {
            define('MODULE_PAYMENT_PAYREXX_' . strtoupper($method) . '_TITLE', str_replace('_', ' ', ucfirst($method)));
            define('MODULE_PAYMENT_PAYREXX_' . strtoupper($method) . '_DESC', MODULE_PAYMENT_PAYREXX_METHOD_DESC . ucfirst($method) .'?');
            $config[strtoupper($method)] = ['value' => 'False','type'  => 'switcher'];
        }

        return $config;
    }

    function getPaymentMethods()
    {
        $paymentMethods =  [
            'masterpass', 'mastercard', 'visa', 'apple_pay', 'maestro', 'jcb', 'american_express', 'wirpay',
            'paypal', 'bitcoin', 'sofortueberweisung_de', 'airplus', 'billpay', 'bonuscard', 'cashu', 'cb',
            'diners_club', 'direct_debit', 'discover', 'elv', 'ideal', 'invoice', 'myone', 'paysafecard',
            'postfinance_card', 'postfinance_efinance', 'swissbilling', 'twint', 'barzahlen', 'bancontact',
            'giropay', 'eps', 'google_pay', 'klarna_paynow', 'klarna_paylater', 'oney'
        ];

        return $paymentMethods;
    }

}

MainFactory::load_origin_class('payrexx');
