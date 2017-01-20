<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

$gatewayModuleName = basename(__FILE__, '.php');

$gatewayParams = getGatewayVariables($gatewayModuleName);

if (!$gatewayParams['type'])
    die("Module Not Activated");

$status = 'success';
$invoiceId = checkCbInvoiceID($_POST['WMI_PAYMENT_NO'], $gatewayParams['name']);
checkCbTransID($_POST['WMI_ORDER_ID']);


if ($params['signatureMethod'] != 'none')
    if (!CheckSignature($_POST, $gatewayParams['signature'], $gatewayParams['signatureMethod']))
        $status = 'Hash Verification Failure';

logTransaction($gatewayParams['name'], $_POST, $status);

if ($status == 'success') {
    addInvoicePayment(
            $invoiceId, $_POST['WMI_ORDER_ID'], $_POST['WMI_PAYMENT_AMOUNT'], $_POST['WMI_COMMISSION_AMOUNT'], $gatewayModuleName
    );

    PrintStatus('OK', 'Оплата принята');
}

function CheckSignature($post, $sign, $metod) {
    foreach ($post as $name => $value) {
        if ($name !== "WMI_SIGNATURE")
            $params[$name] = htmlspecialchars_decode($value);
    }

    uksort($params, "strcasecmp");

    $values = implode('', $params);

    $signature = base64_encode(pack("H*", call_user_func($metod, $values . $sign)));

    if ($signature != $post["WMI_SIGNATURE"]) {
        PrintStatus('RETRY', 'Что-то пошло не так');
        logModuleCall($module = 'walletone', $action = 'CheckSignature', $requeststring = [ 'strArgumentSign' => $values, 'post' => $post, 'sign' => $sign, 'metod' => $metod], $responsedata = null, $processeddata = ['Status' => 'Error signature verification', 'My sign' => $signature], $replacevars = null);
        return FALSE;
    }

    logModuleCall($module = 'walletone', $action = 'CheckSignature', $requeststring = [ 'strArgumentSign' => $values, 'post' => $post, 'sign' => $sign, 'metod' => $metod], $responsedata = null, $processeddata = ['Status' => 'OK', 'My sign' => $signature], $replacevars = null);

    return TRUE;
}

function PrintStatus($status, $DESCRIPTION) {
    $data = "WMI_RESULT=" . strtoupper($status) . "&" . "WMI_DESCRIPTION=" . urlencode($DESCRIPTION);
    echo $data;
    return TRUE;
}
