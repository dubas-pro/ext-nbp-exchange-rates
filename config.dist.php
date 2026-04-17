<?php

$packageJson = file_get_contents('package.json');

if ($packageJson === false) {
    throw new RuntimeException('Failed to read package.json');
}

$version = null;

try {
    $version = json_decode($packageJson, true)['version'];
} catch (JsonException) {
}

if (!is_string($version)) {
    $version = '@@version';
}

return [
    'version' => $version,
    'adminPanelIframeDisabled' => true,
    'customPrefixDisabled' => true,
    'logger' => [
        'path' => 'data/logs/espo.log',
        'level' => 'DEBUG',
        'rotation' => false,
    ],
    'thousandSeparator' => ' ',
    'decimalMark' => ',',
    'timeZone' => 'Europe/Warsaw',
    'weekStart' => 1,
    'dateFormat' => 'DD/MM/YYYY',
    'timeFormat' => 'HH:mm',
    'currencyFormat' => 1,
    'currencyDecimalPlaces' => 2,
    'currencyList' => [
        0 => 'USD',
        1 => 'EUR',
        2 => 'PLN'
    ],
    'defaultCurrency' => 'PLN',
    'baseCurrency' => 'PLN',
    'currencyRates' => [
        'USD' => 1,
        'EUR' => 1
    ],
];
