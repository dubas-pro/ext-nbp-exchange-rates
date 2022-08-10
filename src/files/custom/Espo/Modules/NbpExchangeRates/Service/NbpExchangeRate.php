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

namespace Espo\Modules\NbpExchangeRates\Service;

use Espo\Core\DataManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Utils\Log;
use Espo\Modules\NbpExchangeRates\Tools\Nbp\Api as NbpApi;
use Throwable;

class NbpExchangeRate
{
    private const BASE_CURRENCY_CODE = 'PLN';

    private NbpApi $nbpApi;

    private Config $config;

    private ConfigWriter $configWriter;

    private DataManager $dataManager;

    private Log $log;

    public function __construct(
        NbpApi $nbpApi,
        Config $config,
        ConfigWriter $configWriter,
        DataManager $dataManager,
        Log $log
    ) {
        $this->nbpApi = $nbpApi;
        $this->config = $config;
        $this->configWriter = $configWriter;
        $this->dataManager = $dataManager;
        $this->log = $log;
    }

    public function updateExchangeRates(): void
    {
        $baseCurrency = $this->config->get('baseCurrency');

        if (!$baseCurrency) {
            return;
        }

        // Update rates only when PLN is the base currency.
        if ($baseCurrency !== self::BASE_CURRENCY_CODE) {
            return;
        }

        $table = $this->config->get('nbpExchangeRateTable') ?? 'A';

        $currencyRates = $this->config->get('currencyRates') ?? [];
        foreach ($currencyRates as $currencyCode => $rate) {
            try {
                $exchangeRates = $this->nbpApi->getExchangeRates($table, $currencyCode, 'last/2');
            } catch (Throwable $e) {
                $this->log->warning('NBP API: Unable to retrieve exchange rates for ' . $currencyCode . ' from table ' . $table, [
                    $e->getMessage(),
                ]);

                continue;
            }

            if (empty($exchangeRates)) {
                continue;
            }

            /**
             * The NBP API returns rates from oldest to newest. We want average
             * exchange rate of the last working day hence 0.
             */
            $index = 0;

            /**
             * If today's exchange rate has not yet been published get the most
             * recent rate available. The National Bank of Poland publishes
             * current exchange rates every business day between 11:45 a.m. and
             * 12:15 p.m.
             */
            if ($exchangeRates->rates[1]->effectiveDate !== date('Y-m-d')) {
                $index = 1;
            }

            $currencyRates[$currencyCode] = $exchangeRates->rates[$index]->mid;
        }

        $this->configWriter->set('currencyRates', $currencyRates);
        $this->configWriter->save();
        $this->dataManager->rebuildDatabase([]);
    }
}
