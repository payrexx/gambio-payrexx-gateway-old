<?php

chdir('../../');

define('_VALID_XTC', true);

include ('includes/application_top.php');
include_once('includes/modules/payment/Payrexx/payrexx.php');

try {
    $payrexx = new payrexx();

    $data = $_POST;
    $transaction = $data['transaction'];
    $orderId = end(explode('_', $transaction['referenceId']));

    if (!$orderId || !$transaction['status'] || !$transaction['id']) {
        throw new \Exception('Payrexx Webhook Data incomplete');
    }

    $order = new order($orderId);
    if (!$order) {
        throw new \Exception('Malicious request');
    }

    $payrexxTransaction = $payrexx->getTransactionById($transaction['id']);
    if ($payrexxTransaction->getStatus() !== $transaction['status']) {
        throw new \Exception('Fraudulent transaction status');
    }
    $payrexx->handleTransactionStatus($transaction);
    echo 'Success: Webhook processed!';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
exit();
