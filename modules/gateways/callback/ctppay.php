<?php
/**
 * WHMCS CTPpay Payment Callback File
 */

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);
$systemUrl     = $gatewayParams['systemurl'];

// Die if module is not active.
if (!$gatewayParams['type']) {
    die('Module Not Activated');
}

/**
 * Decript answer
 *
 * @param $data
 * @param $key
 *
 * @return false|string
 */
function ctppay_decrypt($data, $key) {
    $c             = base64_decode($data);
    $ivlen         = openssl_cipher_iv_length($cipher="AES-128-CBC");
    $iv            = substr($c, 0, $ivlen);
    $hmac          = substr($c, $ivlen, $sha2len = 32);
    $cipherdataRaw = substr($c, $ivlen+$sha2len);
    $data          = openssl_decrypt($cipherdataRaw, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
    $calcmac       = hash_hmac('sha256', $cipherdataRaw, $key, $asBinary = true);

    if (hash_equals($hmac, $calcmac)) {
        return $data;
    }

    return false;
}

$data      = isset($_POST['data']) ? (string) $_POST['data'] : null;
$secureKey = $gatewayParams['secureKey'];
$confirm   = false;
$invoiceId = null;
$status    = null;
$amount    = 0;

if ($data) {
    if ($str = ctppay_decrypt($data, $secureKey)) {
        $confirm = json_decode($str, true);
    }
}

if ($confirm) {
    $invoiceId = $confirm[0];
    $status    = $confirm[1];
    $amount    = $confirm[2];
}

$success           = ($status === 'Confirmid');
$transactionStatus = $success ? 'Success' : 'Failure';

if ($invoiceId) {
    // Validate Callback Invoice ID.
    $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

    /**
     * Log Transaction.
     */
    logTransaction($gatewayParams['name'], $_POST, $transactionStatus);

    if ($success) {
        /**
         * Add Invoice Payment.
         *
         * Applies a payment transaction entry to the given invoice ID.
         *
         * @param int $invoiceId         Invoice ID
         * @param string $transactionId  Transaction ID
         * @param float $paymentAmount   Amount paid (defaults to full balance)
         * @param float $paymentFee      Payment fee (optional)
         * @param string $gatewayModule  Gateway module name
         */
        addInvoicePayment(
            $invoiceId,
            $invoiceId.'<br>'.$data,
            $amount,
            0,
            $gatewayModuleName
        );
    }

    header('Location: '.$systemUrl.'viewinvoice.php?id='.$invoiceId);
} else {
    header('Location: '.$systemUrl.'clientarea.php?action=masspay&all=true');
}
