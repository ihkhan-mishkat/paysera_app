<?php
$cURLConnection = curl_init();

curl_setopt($cURLConnection, CURLOPT_URL, 'http://api.exchangeratesapi.io/v1/latest?access_key=8a3d653d96d12c6715c852a839c7805b&base=EUR&symbols=USD,JPY');
curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

$apiResponse = curl_exec($cURLConnection);
// $apiResponse = '{"success":true,"timestamp":1640278744,"base":"EUR","date":"2021-12-23","rates":{"USD":1.131669,"JPY":129.497476}}';
curl_close($cURLConnection);

// $apiResponse - available data from the API request
$currency = json_decode($apiResponse);

return [

    'inputCommissionPercent' => 0.0003,

    'inputCommissionLimitMax' => 5,

    'outputCommissionPercentPrivate' => 0.003,

    'outputCommissionPercentBusiness' => 0.005,

    'outputCommissionBusinessLimitMin' => 0.5,

    'outputCommissionPrivateFreeTransactions' => 3,
    'outputCommissionPrivateDiscount' => 1000,

    'commissionPrecision' => 2,

    'currencyConversion' => [
        'EUR' => 1,
        'USD' => $currency->rates->USD,
        'JPY' => $currency->rates->JPY,
    ],

];