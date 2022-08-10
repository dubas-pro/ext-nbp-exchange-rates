<?php
/**
 * This file is part of the NBP Exchange Rates - EspoCRM extension.
 *
 * DUBAS S.C. - contact@dubas.pro
 * Copyright (C) 2022-2022 Arkadiy Asuratov, Emil Dubielecki
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Espo\Modules\NbpExchangeRates\Tools\Nbp;

use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Json;
use stdClass;
use Throwable;

class Api
{
    private const BASE_URL = 'https://api.nbp.pl/api';

    private const TIMEOUT = 10;

    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function request(string $params): stdClass
    {
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $baseUrl = rtrim(
            $this->config->get('nbpApiBaseUrl') ??
            self::BASE_URL
        );

        $timeout = $this->config->get('nbpApiTimeout') ?? self::TIMEOUT;
        $url = $baseUrl . '/' . $params . '/?format=json';

        $ch = curl_init();
        curl_setopt($ch, \CURLOPT_URL, $url);
        curl_setopt($ch, \CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, \CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, \CURLOPT_HEADER, true);
        curl_setopt($ch, \CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, \CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, \CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, \CURLINFO_HEADER_SIZE);
        $body = mb_substr($response, $headerSize);

        if ($code !== 200) {
            throw new Error('NBP API: Unexpected HTTP code ' . $code);
        }

        try {
            $body = Json::decode($body);
        } catch (Throwable $e) {
            $body = (object) [];
        }

        if (isset($body->error)) {
            throw new Error('NBP API: Unexpected error ' . $body->error);
        }

        return $body;
    }

    public function getExchangeRates(string $table, string $currencyCode, string $params = ''): stdClass
    {
        return $this->request('exchangerates/rates/' . $table . '/' . $currencyCode . '/' . $params);
    }
}
