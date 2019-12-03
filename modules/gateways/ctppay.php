<?php
/**
 * WHMCS CTPpay Payment Gateway Module
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Define module related meta data.
 *
 * @return array
 */
function ctppay_MetaData()
{
    return array(
        'DisplayName'                => 'CTPpay Payment Gateway Module',
        'APIVersion'                 => '1.1',
        'DisableLocalCredtCardInput' => false,
        'TokenisedStorage'           => false,
    );
}

/**
 * Define gateway configuration options.
 *
 * @return array
 */
function ctppay_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type'  => 'System',
            'Value' => 'CTPpay Payment Gateway Module',
        ),
        // a text field type allows for single line text input
        'merchantKey' => array(
            'FriendlyName' => 'Merchant Key',
            'Type' => 'text',
            'Size' => '24',
            'Default' => '',
            'Description' => 'Enter your account merchant key here',
        ),
        // a password field type allows for masked text input
        'secureKey' => array(
            'FriendlyName' => 'Secure Key',
            'Type'         => 'password',
            'Size'         => '24',
            'Default'      => '',
            'Description'  => 'Enter secure key here',
        ),
        // the dropdown field type renders a select menu of options
        'availableCurrency' => array(
            'FriendlyName' => 'Сurrencies',
            'Type' => 'dropdown',
            'Options' => array(
                'ALL' => 'ALL',
                'USD' => 'USD',
                'EUR' => 'EUR',
                'RUB' => 'RUB',
            ),
            'Default'     => 'ALL',
            'Description' => 'Choose one',
        ),
    );
}

/**
 * Payment link.
 *
 * @return string
 */
function ctppay_link($params)
{
    $availableCurrency = $params['availableCurrency'];
    $invoiceCurrency   = strtoupper($params['currency']);

    if ($availableCurrency === 'ALL') {
        if (!in_array($invoiceCurrency, array('USD', 'EUR', 'RUB'))) {
            return '';
        }
    } elseif ($availableCurrency !== $invoiceCurrency) {
        return '';
    }

    // System Parameters
    $systemUrl  = rtrim($params['systemurl'], '/');
    $langPayNow = $params['langpaynow'];
    $moduleName = $params['paymentmethod'];

    $postfields = array(
        'key'    => $params['merchantKey'],
        'order'  => $params['invoiceid'],
        'pay'    => $invoiceCurrency,
        'volume' => $params['amount'],
        'ref'    => $systemUrl . '/modules/gateways/callback/' . $moduleName . '.php',
    );

    $htmlOutput = '
            <style>
                    body {
                        background: #fff;
                    }
                
                    form {
                        margin: 0;
                        padding: 0;
                    }
                
                    .ctppayWrapper {
                        border: 1px solid rgba(196, 21, 28, 0.50);
                        padding: 2rem;
                        margin: 0 auto;
                        border-radius: 2px;
                        margin-top: 2rem;
                        box-shadow: 0 7px 5px #eee;
                    }
            
            
                    .ctppayWrapper button {
                        background: rgba(196, 21, 28, 1);
                        border: none;
                        color: #fff;
                        width: 120px;
                        height: 40px;
                        line-height: 25px;
                        font-size: 16px;
                        font-family: sans-serif;
                        text-transform: uppercase;
                        border-radius: 2px;
                        cursor: pointer;
                    }
                h3 {
                    text-align: center;
                    margin-top: 3rem;
                    color: rgba(196, 21, 28, 1);
                }
            </style>
            <script>
                function submitForm() {
                    document.ctppay.submit();
                }
            </script>
            <script src="https://sandbox.jazzcash.com.pk/Sandbox/Scripts/hmac-sha256.js"></script>
            
            <!--<h3>JazzCash HTTP POST (Page Redirection) Testing</h3>-->
            <div class="ctppayWrapper text-center">';

    $htmlOutput .= '<form name="ctppay" method="get" action="https://merchant.ctppay.com/merchant/">';

    foreach ($postfields as $k => $v) {
        $htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . htmlspecialchars($v) . '" />';
    }

    $htmlOutput .= '<button type="button" class="text-center" onclick="submitForm()">' . $langPayNow . '</button>';
    $htmlOutput .= '</form>';

    return $htmlOutput;
}

/**
 * Cancel subscription.
 *
 * @return array Transaction response status
 */
function ctppay_cancelSubscription($params)
{
    return array(
        // 'success' if successful, any other value for failure
        'status' => 'success',
        // Data to be recorded in the gateway log - can be a string or array
        'rawdata' => 'Cancel CTPpay subscription',
    );
}
