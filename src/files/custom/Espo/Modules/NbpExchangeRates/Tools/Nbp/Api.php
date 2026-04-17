<?php

/**
 * This file is part of the NBP Exchange Rates - EspoCRM extension.
 *
 * dubas s.c. - contact@dubas.pro
 * Copyright (C) 2022-2026 Arkadiy Asuratov, Emil Dubielecki
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

use const CURLINFO_HEADER_SIZE;
use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_HEADER;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;
use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Json;
use JsonException;
use stdClass;

class Api
{
    private const BASE_URL = 'https://api.nbp.pl/api';

    private const TIMEOUT = 10;

    public function __construct(
        private readonly Config $config
    )
    {}

    public function request(string $params): stdClass
    {
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $url = $this->getBaseUrl() . '/' . $params . '/?format=json';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->getTimeout());
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->getTimeout());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        /** @var string|false $response */
        $response = curl_exec($ch);

        if ($response === false) {
            $response = '';
        }

        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = mb_substr($response, $headerSize);

        if ($code !== 200) {
            throw new Error('NBP API: Unexpected HTTP code ' . $code);
        }

        try {
            $body = Json::decode($body);
        } catch (JsonException) {}

        if (!($body instanceof stdClass)) {
            $body = (object) [];
        }

        if (isset($body->error) && is_string($body->error) && $body->error !== '') {
            throw new Error('NBP API: Unexpected error ' . $body->error);
        }

        return $body;
    }

    public function getExchangeRates(string $table, string $currencyCode, string $params = ''): ?NbpExchangeRates
    {
        $response = $this->request('exchangerates/rates/' . $table . '/' . $currencyCode . '/' . $params);

        return NbpExchangeRates::fromRaw($response);
    }

    private function getBaseUrl(): string
    {
        $url = $this->config->get('nbpApiBaseUrl');

        if (is_string($url) && $url !== '') {
            return rtrim($url);
        }

        return self::BASE_URL;
    }

    private function getTimeout(): int
    {
        $timeout = $this->config->get('nbpApiTimeout');

        if (is_int($timeout) && $timeout > 0) {
            return $timeout;
        }

        return self::TIMEOUT;
    }
}
