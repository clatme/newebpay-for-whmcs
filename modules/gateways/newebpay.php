<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function newebpay_MetaData()
{
    return array(
        'DisplayName' => 'newebpay',
        'APIVersion' => '1.5', // Use API Version
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}

function newebpay_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Newebpay (藍新金流)',
        ),
        // a text field type allows for single line text input
        'merchantID' => array(
            'FriendlyName' => 'MerchantID (商店代號)',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => '在此處輸入您的藍新金流商店代號',
        ),
        // a password field type allows for masked text input
        'hashKey' => array(
            'FriendlyName' => 'Hash Key',
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => '在此輸入您生成的 Hash Key',
        ),
        'hashIV' => array(
            'FriendlyName' => 'Hash IV',
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => '在此輸入您生成的 Hash IV',
        ),
        // the yesno field type displays a single checkbox option
        'testMode' => array(
            'FriendlyName' => '測試模式',
            'Type' => 'yesno',
            'Description' => '勾選以啟用測試模式。',
        ),
    );
}

function newebpay_link($params)
{
    // 網關參數
    $merchantID = $params['merchantID'];
    $hashKey = $params['hashKey'];
    $hashIV = $params['hashIV'];
    $testMode = $params['testMode'];

    // 帳單參數
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    //由於支付接口不支援小數金額，因此取整數金額。
    $amount = explode('.',$params['amount']);

    // 客戶參數
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];

    // 系統參數
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    //是否為測試模式
    if($testMode == true) {
        $url = 'https://ccore.newebpay.com/MPG/mpg_gateway';
    } else {
        $url = 'https://core.newebpay.com/MPG/mpg_gateway';
    }

    //交易參數
    $postfields = array();
    $postfields = array(
        'MerchantID' => trim($merchantID),
        'RespondType' => 'JSON',
        'TimeStamp' => time(),
        'Version' => '1.5',
        'MerchantOrderNo' => $invoiceId,
        'Amt' => $amount['0'],
        'ItemDesc' => $description,
        'ReturnURL' => $returnUrl,
        'NotifyURL' => $systemUrl .'modules/gateways/callback/newebpay.php',
        'ClientBackURL' => $returnUrl,
        'Email' => $email,
        'EmailModify' => '0',
        'LoginType' => '0'
    );
    $aesData = getAES($postfields, $hashKey, $hashIV);
    $sha256Data = aes_sha256_str($aesData, $hashKey, $hashIV);

    //需要傳送的參數
    $transData = array(
        'MerchantID' => trim($merchantID),
        'TradeInfo' => $aesData,
        'TradeSha' => $sha256Data,
        'Version' => '1.5',
    );

    //生成需要提交的表單
    $htmlOutput = '<form method="post" action="' . $url . '">';
    foreach ($transData as $key => $value) {
        $htmlOutput .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
    }
    $htmlOutput .= '<input class="btn btn-success" type="submit" value="' . $langPayNow . '" />';
    $htmlOutput .= '</form>';

    return $htmlOutput;
}

//AES 加密
function getAES($postData, $hashKey, $hashIV) 
{ 
    $return_str = '';
    if (!empty($postData)) {
        //將參數經過 URL ENCODED QUERY STRING
        $return_str = http_build_query($postData); 
    }
    return trim(bin2hex(openssl_encrypt(addpadding($return_str), 'aes-256-cbc', $hashKey, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $hashIV)));
}
function addpadding($string, $blocksize = 32) 
{
    $len = strlen($string);
    $pad = $blocksize - ($len % $blocksize); 
    $string .= str_repeat(chr($pad), $pad); 
    return $string;
}
//SHA256 加密
function aes_sha256_str($aesData, $hashKey, $hashIV)
{
    return strtoupper(hash("sha256", 'HashKey='.$hashKey.'&'.$aesData.'&HashIV='.$hashIV));
}
