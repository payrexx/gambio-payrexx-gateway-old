<?php

chdir('../../');

define('_VALID_XTC', true);

include ('includes/application_top.php');
include_once('includes/modules/payment/Payrexx/payrexx.php');

try {
    $payrexx = new payrexx();
    $payrexx->handleTransactionStatus();
    echo 'Success: Webhook processed!';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
exit();
