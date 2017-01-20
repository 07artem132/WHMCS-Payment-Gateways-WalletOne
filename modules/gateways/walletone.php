<?php

/**
 * 
 * @author Artem Ivanko <a_ivanko@service-voice.com>
 */
function walletone_config() {
    $configarray = array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'walletone'
        ),
        'IDmerchant' => array(
            'FriendlyName' => 'Индентификатор (номер кассы)',
            'Type' => 'text',
            'Size' => '15',
            'Default' => '',
            'Description' => 'Индентификатор (номер кассы) интернет-магазина, сгенерированный на сайте WalletOne',
        ), 'signatureMethod' => array(
            'FriendlyName' => 'Метод формирования ЭЦП',
            'Type' => 'dropdown',
            'Options' => array(
                'MD5' => 'md5',
                'SHA1' => 'sha1',
                'none' => 'none',
            ),
            'Description' => 'Метод формирования ЭЦП, который выбран в личном кабинете кассы',
        ),
        'signature' => array(
            'FriendlyName' => 'Ключ (ЭЦП) интернет-магазина',
            'Type' => 'password',
            'Size' => '250',
            'Default' => '',
            'Description' => 'Код, который сгенерирован в личном кабинете кассы',
        ),
        'currencyId' => array(
            'FriendlyName' => 'Идентификатор валюты по умолчанию',
            'Type' => 'dropdown',
            'Options' => array(
                643 => 'RUB',
                710 => 'ZAR',
                840 => 'USD',
                978 => 'EUR',
                980 => 'UAH',
                398 => 'KZT',
                974 => 'BYR',
                972 => 'TJS',
                985 => 'PLN',
                981 => 'GEL'
            ),
            'Description' => 'При любых валютах на сайте, платеж будет идти всегда по выбранной валюте по умолчанию',
        ), 'WMI_AUTO_LOCATION' => array(
            'FriendlyName' => 'Показыать все способы оплаты ?',
            'Type' => 'yesno',
            'Default' => 'yes',
            'Description' => 'Если галочка не установлена страна пользователя и подходяшие способы оплаты определяются по IP',
        ),
        'URLscript' => array(
            'FriendlyName' => 'URL скрипта',
            'Description' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/modules/gateways/callback/walletone.php',
        ),
        'author' => array(
            'FriendlyName' => 'Author',
            'Description' => 'Artem Ivanko <a href="mailto:a_ivanko@service-voice.com">a_ivanko@service-voice.com</a>',
        ),
        'Poweredby' => array(
            'FriendlyName' => 'Powered by',
            'Description' => 'Service-Voice',
        )
    );
    return $configarray;
}

function walletone_link($params) {
    global $_LANG;

    $fields = array();
    $fields['WMI_CURRENCY_ID'] = $params['currencyId'];
    $fields['WMI_DESCRIPTION'] = "BASE64:" . base64_encode('Invoices #' . $params['invoiceid']);
    $fields['WMI_SUCCESS_URL'] = $params['systemurl'] . 'viewinvoice.php?id=' . $params['invoiceid'] . '&paymentsuccess=true&do=paid';
    $fields['WMI_FAIL_URL'] = $params['systemurl'] . 'viewinvoice.php?id=' . $params['invoiceid'] . '&paymentfailed=true';
    $fields['WMI_PAYMENT_NO'] = $params['invoiceid'];
    $fields['WMI_MERCHANT_ID'] = $params['IDmerchant'];
    $fields['WMI_CUSTOMER_EMAIL'] = $params['clientdetails']['email'];
    $fields['WMI_RECIPIENT_LOGIN'] = $params['clientdetails']['email'];
    $fields['WMI_PAYMENT_AMOUNT'] = number_format($params['amount'], 2, '.', '');
    $fields['WMI_EXPIRED_DATE'] = date('Y-m-d\TH:i:s', time() + 10000000);

    if (!empty($params['clientdetails']['firstname']))
        $fields['WMI_CUSTOMER_FIRSTNAME'] = $params['clientdetails']['firstname'];

    if (!empty($params['clientdetails']['lastname']))
        $fields['WMI_CUSTOMER_LASTNAME'] = $params['clientdetails']['lastname'];

    if ($params['WMI_AUTO_LOCATION'] == 'on')
        $fields['WMI_AUTO_LOCATION'] = '0';

    uksort($fields, "strcasecmp");

    if ($params['signatureMethod'] != 'none')
        $fields["WMI_SIGNATURE"] = createSignature($fields, $params['signature'], $params['signatureMethod']);

    $code = '<form action="https://wl.walletone.com/checkout/checkout/Index" accept-charset="UTF-8" method="POST">';
    foreach ($fields as $key => $value) {
        $code .= '<input type="hidden" name="' . $key . '" value="' . $value . '"/>';
    }
    $code .= '<input type="submit" value="' . $_LANG['invoicespaynow'] . '" class="button" /></form>';

    logModuleCall($module = 'walletone', $action = 'walletone_link_create', $requeststring = [ 'params' => $params], $responsedata = null, $processeddata = ['code' => $code], $replacevars = null);

    return $code;
}

/**
 * Create signature.
 * 
 * @param array $fields
 * @param string $key
 * @param string $method
 * @return string
 */
function createSignature($fields, $key, $method) {
    $fieldValues = "";
    foreach ($fields as $value) {
        if (is_array($value)) {
            foreach ($value as $v) {
                $v = iconv("utf-8", "windows-1251", $v);
                $fieldValues .= $v;
            }
        } else {
            $value = iconv("utf-8", "windows-1251", $value);
            $fieldValues .= $value;
        }
    }

    $signature = base64_encode(pack("H*", $method($fieldValues . $key)));
    return $signature;
}
