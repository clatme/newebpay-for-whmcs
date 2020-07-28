<?php

//加載所需的庫
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

//從文件名稱獲取模塊名稱
$gatewayModuleName = basename(__FILE__, '.php');

//獲取網關配置參數
$gatewayParams = getGatewayVariables($gatewayModuleName);

//檢查模塊是否啟用
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

//設定的模塊參數
$merchantID = $gatewayParams['merchantID'];
$hashKey = $gatewayParams['hashKey'];
$hashIV = $gatewayParams['hashIV'];

//獲取回傳的參數
$status = $_REQUEST["Status"];
$resultMerchantID = $_REQUEST["MerchantID"];
$tradeInfo = json_decode(create_aes_decrypt($_REQUEST["TradeInfo"], $hashKey, $hashIV),true);
$transData = $tradeInfo['Result'];

//解密回傳的AES數據
function create_aes_decrypt($tradeInfo, $hashKey, $hashIV) {
    return strippadding(openssl_decrypt(hex2bin($tradeInfo),'AES-256-CBC', $hashKey, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $hashIV)); 
}
function strippadding($string) {
    $slast = ord(substr($string, -1));
    $slastc = chr($slast);
    $pcheck = substr($string, -$slast);
    if (preg_match("/$slastc{" . $slast . "}/", $string)) {
        $string = substr($string, 0, strlen($string) - $slast);
        return $string; 
    } else {
        return false; 
    }
}

//檢查帳單編號
$invoiceId = checkCbInvoiceID($transData['MerchantOrderNo'], $gatewayParams['name']);

//檢查是否已有相同的交易編號
checkCbTransID($transData['TradeNo']);

//交易日誌
logTransaction($gatewayParams['name'], $_REQUEST, $status);

//如果數據無誤，添加付款紀錄
if ($status == 'SUCCESS' && $resultMerchantID == $merchantID) {
    addInvoicePayment(
        $invoiceId,
        $transData['TradeNo'],
        $transData['Amt'],
        $paymentFee,
        $gatewayModuleName
    );
}
