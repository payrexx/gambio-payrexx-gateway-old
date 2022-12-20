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
     * @var bool
     */
    public $tmpOrders = true;


    /**
     * Order status
     */
    const STATUS_REFUNDED = 'Payrexx refunded';
    const STATUS_PARTIALLY_REFUNDED = 'Payrexx partially refunded';
    const STATUS_PENDING = 'Pending';
    const STATUS_PROCESSING = 'Processing';
    const STATUS_CANCELED = 'Canceled';

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
        $payrexx = $this->getInterface();
        $signatureCheck = new \Payrexx\Models\Request\SignatureCheck();
        try {
            $response = $payrexx->getOne($signatureCheck);
            return true;
        } catch (\Payrexx\PayrexxException $e) {
            return false;
        }
    }

    /**
     * Get Transaction by transaction id
     *
     * @param integer $id
     * @return array|Transaction
     */
    public function getTransactionById(int $id)
    {
        $payrexx = $this->getInterface();
        $transaction = new Transaction();
        $transaction->setId($id);

        try {
            $response = $payrexx->getOne($transaction);
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

        if (!$this->_validateSignature()) return false;
        $selection = [
            'id' => $this->code,
//            'module' => constant('MODULE_PAYMENT_PAYREXX_DISPLAY_NAME_' . strtoupper($_SESSION['language_code'])),
            'module' => MODULE_PAYMENT_PAYREXX_CHECKOUT_NAME,
            'description' => $this->_getDescription(),
        ];

        return $selection;
    }

    /**
     * @return string
     */
    protected function _getDescription()
    {
        $description = MODULE_PAYMENT_PAYREXX_CHECKOUT_DESCRIPTION;
        $description .= '<style> .payrexx .payment-module-icon img{background: initial !important;}</style><br>';
        foreach ($this->getPaymentMethods() as $method) {
            if (constant('MODULE_PAYMENT_PAYREXX_' . strtoupper($method)) === 'true') {
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

    /**
     * Execute after order saved
     */
    function payment_action()
    {
        global $insert_id;

        $orderId = $insert_id;
        if (isset($_GET['payrexx_success'])) {
            return false;
        }

        try {
            $response = $this->createPayrexxGateway($orderId);
        } catch (\Payrexx\PayrexxException $e) {
            return false;
        }
        $payrexxPaymentUrl = str_replace('?', $_SESSION['language_code'] . '/?', $response->getLink());
        xtc_redirect($payrexxPaymentUrl);
    }

    /**
     * Create Payrexx Gateway
     *
     * @param int $orderId
     * @return \Payrexx\Models\Response\Gateway
     */
    public function createPayrexxGateway($orderId)
    {
        $order = new order($orderId);
        $currency = $order->info['currency'];
        $totalAmount = $order->info['pp_total'] * 100;

        // Basket
        $basket = $this->collectBasketData();
        $basketAmount = 0;
        foreach ($basket as $basketItem) {
            $basketAmount += $basketItem['quantity'] * $basketItem['amount'];
        }

        // Purpose
        $purpose = null;
        if (round($basketAmount) !== round($totalAmount)) {
            $purpose = $this->createPurposeByBasket($basket);
            $basket = [];
        }

        // Reference
        $referenceId = $orderId;
        if (!empty(MODULE_PAYMENT_PAYREXX_PREFIX)) {
            $referenceId = MODULE_PAYMENT_PAYREXX_PREFIX . '_' . $orderId;
        }

        // Redirect URL
        $successUrl = xtc_href_link(FILENAME_CHECKOUT_PROCESS, 'payrexx_success=1', 'SSL');
        $failedUrl = xtc_href_link(FILENAME_CHECKOUT_PROCESS, 'payrexx_failed=1', 'SSL');
        $cancelUrl = xtc_href_link(FILENAME_CHECKOUT_PROCESS, 'payrexx_cancel=1', 'SSL');

        $gateway = new \Payrexx\Models\Request\Gateway();
        $gateway->setAmount($totalAmount);
        $gateway->setCurrency($currency);

        $gateway->setSuccessRedirectUrl($successUrl);
        $gateway->setFailedRedirectUrl($failedUrl);
        $gateway->setCancelRedirectUrl($cancelUrl);

        $gateway->setPsp([]);

        $gateway->setReferenceId($referenceId);
        $gateway->setValidity(15);

        $gateway->setBasket($basket);
        $gateway->setPurpose($purpose);

        $gateway->setSkipResultPage(true);

        $gateway->addField('forename', $order->billing['firstname']);
        $gateway->addField('surname', $order->billing['lastname']);
        $gateway->addField('company', $order->billing['company']);
        $gateway->addField('street', $order->billing['street_address']);
        $gateway->addField('postcode', $order->billing['postcode']);
        $gateway->addField('place', $order->billing['city']);
        $gateway->addField('country', $order->billing['country_iso_2']);
        $gateway->addField('phone', $order->customer['telephone']);
        $gateway->addField('email', $order->customer['email_address']);
        $gateway->addField('custom_field_1', $orderId, 'Gambio Order ID');

        if (!empty(MODULE_PAYMENT_PAYREXX_LOOK_AND_FEEL_ID)) {
            $gateway->setLookAndFeelProfile(MODULE_PAYMENT_PAYREXX_LOOK_AND_FEEL_ID);
        }

        $payrexx = $this->getInterface();
        $response = $payrexx->create($gateway);

        return $response;
    }

    /**
     * Collect basket data
     *
     * @return array
     */
    public function collectBasketData(): array
    {
        global $order;

        $customerStatus = $_SESSION['customers_status'];
        $addTaxToBasket = $customerStatus['customers_status_show_price_tax'] == 0 &&
            $customerStatus['customers_status_add_tax_ot'] == 1;

        $basketItems = [];
        foreach ($order->products as $item) {
            $basketItems[] = [
                'name' => [
                    2 => $item['name']
                ],
                'description' => [
                    2 => $item['checkout_information']
                ],
                'quantity' => (int) $item['qty'],
                'amount' => round($item['price'] * 100),
            ];
        }

        // Discount
        if (isset($order->info['deduction']) && $order->info['deduction'] > 0) {
            $basketItems[] = [
                'name' => [
                    2 => 'Discount',
                ],
                'quantity' => 1,
                'amount' => -(round($order->info['deduction'] * 100)),
            ];
        }

        // Shipping
        if (isset($order->info['shipping_cost']) && $order->info['shipping_cost'] > 0) {
            $basketItems[] = [
                'name' => [
                    2 => 'Shipping',
                ],
                'quantity' => 1,
                'amount' => round($order->info['shipping_cost'] * 100),
            ];
        }

        // Tax
        if ($addTaxToBasket && isset($order->info['tax']) && $order->info['tax'] > 0) {
            $basketItems[] = [
                'name' => [
                    2 => 'Tax',
                ],
                'quantity' => 1,
                'amount' => round($order->info['tax'] * 100),
            ];
        }

        return $basketItems;
    }

    /**
     * Create puropose by order.
     *
     * @param array $basket
     * @return string
     */
    public function createPurposeByBasket($basket): string
    {
        $desc = [];
        foreach ($basket as $product) {
            $desc[] = implode(' ', [
                $product['name']['2'],
                $product['quantity'],
                'x',
                number_format($product['amount'] / 100, 2, '.', ','),
            ]);
        }
        return implode('; ', $desc);
    }

    /**
     * @return false
     */
    public function before_process()
    {
       return false;
    }

    /**
     * @return false
     */
    public function after_process()
    {
        global $insert_id;

        if (!isset($_GET['payrexx_cancel']) && !isset($_GET['payrexx_failed'])) {
            return false;
        }

        try {
            $this->handleTransactionStatus($insert_id, Transaction::CANCELLED);
        } catch(Exception $e) {
        }
        // Error messages.
        $errorMessage = isset($_GET['payrexx_cancel'])
            ? MODULE_PAYMENT_PAYREXX_CANCEL
            : MODULE_PAYMENT_PAYREXX_FAILED;
        $_SESSION['gm_error_message'] = urlencode($errorMessage);
        xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL'));
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
        $this->addNewOrderStatus();
    }

    /**
     * Add new order status
     */
    public function addNewOrderStatus()
    {
        $newOrderStatusConfig = $this->getOrderStatusConfig();
        $orderStatusService = StaticGXCoreLoader::getService('OrderStatus');
        foreach ($newOrderStatusConfig as $statusConfig) {
            $newOrderStatus = MainFactory::create('OrderStatus');
            foreach (['en', 'de'] as $lang) {
                $statusName = $statusConfig['names'][$lang] ?? $statusConfig['names']['en'];
                if ($this->orderStatusExists($statusName, $lang)) {
                    continue 2;
                }
                $newOrderStatus->setName(
                    MainFactory::create('LanguageCode', new StringType($lang)),
                    new StringType($statusName)
                );
            }
            $newOrderStatus->setColor(new StringType($statusConfig['color']));
            $orderStatusService->create($newOrderStatus);
        }
    }

    /**
     * Check order status exist
     *
     * @param string $statusName
     * @param string $langCode
     * @return bool|int
     */
    public function orderStatusExists($statusName, $langCode = 'en')
    {
        $orderStatusId = false;
        $orderStatusService = StaticGXCoreLoader::getService('OrderStatus');
        foreach ($orderStatusService->findAll() as $orderStatus) {
            if ($orderStatus->getName(MainFactory::create('LanguageCode', new StringType($langCode))) === $statusName) {
                return $orderStatusId = (int) $orderStatus->getId();
            }
        }
        return $orderStatusId;
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
//        $availableLanguages = $this->_xtc_get_languages();
//        if(!empty($availableLanguages)) {
//            foreach ($availableLanguages as $language) {
//                $config['DISPLAY_NAME_' . strtoupper($language['code'])] = ['value' => $this->title, 'type' => 'text'];
//                $config['DISPLAY_DESCRIPTION_' . strtoupper($language['code'])] = ['value' => $this->info, 'type' => 'text'];
//            }
//        }

        $config['CHECKOUT_NAME'] = ['value' => $this->title, 'type' => 'text'];
        $config['CHECKOUT_DESCRIPTION'] = ['value' => $this->info, 'type' => 'text'];

        /**
         * Creating checkbox for each payment method.
         */
        foreach ($this->getPaymentMethods() as $method) {
            define('MODULE_PAYMENT_PAYREXX_' . strtoupper($method) . '_TITLE', str_replace('_', ' ', ucfirst($method)));
            define('MODULE_PAYMENT_PAYREXX_' . strtoupper($method) . '_DESC', 'Accept payment by ' . ucfirst($method) .'?');
            $config[strtoupper($method)] = ['value' => 'False','type'  => 'switcher'];
        }

        return $config;
    }

    /**
     * Get order status config
     */
    public function getOrderStatusConfig()
    {
        return [
            'refunded' => [
                'names' => [
                    'en' => static::STATUS_REFUNDED,
                    'de' => 'Payrexx Rückerstattung',
                ],
                'color' => '2196F3',
            ],
            'partially-refunded' => [
                'names' => [
                    'en' => static::STATUS_PARTIALLY_REFUNDED,
                    'de' => 'Payrexx Teilrückerstattung',
                ],
                'color' => '2196F3',
            ],
        ];
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

    private function getInterface() {
        return new \Payrexx\Payrexx(MODULE_PAYMENT_PAYREXX_INSTANCE_NAME, MODULE_PAYMENT_PAYREXX_API_KEY, '', trim(MODULE_PAYMENT_PAYREXX_PLATFORM));
    }

    /**
     * Handle webhook data
     *
     * @param int $orderId
     * @param string $status
     * @param array $invoice
     */
    public function handleTransactionStatus(int $orderId, string $status, array $invoice = [])
    {
        // status mapping
        switch ($status) {
            case Transaction::WAITING:
                $newStatusId = 1; // Pending
                $newStatus = static::STATUS_PENDING;
                break;
            case Transaction::CONFIRMED:
                $newStatusId = 2; // Processing
                $newStatus = static::STATUS_PROCESSING;
                break;
            case Transaction::CANCELLED:
            case Transaction::DECLINED:
            case Transaction::ERROR:
            case Transaction::EXPIRED:
                $newStatusId = 99; // Canceled
                $newStatus = static::STATUS_CANCELED;
                break;
            case Transaction::REFUNDED:
            case Transaction::PARTIALLY_REFUNDED:
                $newStatus = $this->getOrderStatusConfig()[$status]['names']['en'];
                $newStatusId = $this->orderStatusExists($newStatus);
                if (!$newStatusId) {
                    $this->addNewOrderStatus();
                    $newStatusId = $this->orderStatusExists($newStatus);
                }
                if (
                    $newStatus == static::STATUS_PARTIALLY_REFUNDED &&
                    !empty($invoice) &&
                    $invoice['originalAmount'] == $invoice['refundedAmount']
                ) {
                    $newStatus = static::STATUS_REFUNDED;
                    $newStatusId = $this->orderStatusExists($newStatus);
                }
                break;
            default:
                throw new \Exception($status . ' case not implemented.');
        }

        // check the status transition to change.
        if (!$this->isAllowedToChangeStatus($orderId, $newStatus)) {
            throw new \Exception('Status transition not allowed');
        }
        $this->updateOrderStatus($orderId, $newStatusId, $newStatus);
    }

    /**
     * Check the transition is allowed or not
     *
     * @param int $orderId
     * @param string $newStatus
     * @return bool
     */
    private function isAllowedToChangeStatus($orderId, $newStatus)
    {
        try {
            $db = StaticGXCoreLoader::getDatabaseQueryBuilder();
            $languages = $db->select('languages_id')
                ->where('code', 'en')
                ->get('languages')
                ->result_array();
            $langId =  $languages[0]['languages_id'];

            $orderStatus = $db->select('orders_status.orders_status_name')
                ->join('orders_status', 'orders.orders_status = orders_status.orders_status_id')
                ->where('orders_status.language_id', $langId)
                ->where('orders.orders_id', $orderId )
                ->limit(1)
                ->get('orders')
                ->result_array();
            $oldStatus = $orderStatus[0]['orders_status_name'];
        } catch (Exception $e) {
            return false;
        }
        switch ($oldStatus) {
            case static::STATUS_PENDING:
                return !in_array($newStatus, [
                    static::STATUS_REFUNDED,
                    static::STATUS_PARTIALLY_REFUNDED,
                ]);
            case static::STATUS_PROCESSING:
            case static::STATUS_PARTIALLY_REFUNDED:
                return in_array($newStatus, [
                    static::STATUS_REFUNDED,
                ]);
        }
        return false;
    }

    /**
     * Update order status
     *
     * @param int $orderId
     * @param int $newStatusId
     * @param string $newStatus
     */
    public function updateOrderStatus(int $orderId, int $newStatusId, string $newStatus)
    {
        $orderWriteService = StaticGXCoreLoader::getService('OrderWrite');
        //update status and customer-history
        $orderWriteService->updateOrderStatus(
            new IdType($orderId),
            new IntType((int)$newStatusId),
            new StringType($newStatus . ' status updated by payrexx'),
            new BoolType(false)
        );
    }
}

MainFactory::load_origin_class('payrexx');
