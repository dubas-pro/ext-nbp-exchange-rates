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

use Espo\Core\Currency\ConfigDataProvider;
use Espo\Core\Field\Date;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use Espo\Entities\CurrencyRecord;
use Espo\Entities\CurrencyRecordRate;
use Espo\Modules\NbpExchangeRates\Tools\Nbp\Api as NbpApi;
use Espo\ORM\EntityManager;
use Throwable;

class Service
{
    private const BASE_CURRENCY_CODE = 'PLN';

    public function __construct(
        private readonly NbpApi $nbpApi,
        private readonly Config $config,
        private readonly Log $log,
        private readonly EntityManager $entityManager,
        private readonly ConfigDataProvider $configDataProvider,
    ) {}

    public function updateExchangeRates(): void
    {
        if ($this->configDataProvider->getBaseCurrency() !== self::BASE_CURRENCY_CODE) {
            $this->log->warning('NBP API: Base currency code is not ' . self::BASE_CURRENCY_CODE . '. Exchange rates update skipped.');

            return;
        }

        $table = $this->getExchangeRateTable();

        $currencyRecords = $this->entityManager
            ->getRDBRepositoryByClass(CurrencyRecord::class)
            ->where([
                CurrencyRecord::FIELD_CODE . '!=' => self::BASE_CURRENCY_CODE,
                CurrencyRecord::FIELD_STATUS => CurrencyRecord::STATUS_ACTIVE,
            ])
            ->find();

        foreach ($currencyRecords as $currencyRecord) {
            $currencyCode = $currencyRecord->getCode();

            try {
                $exchangeRates = $this->nbpApi->getExchangeRates($table, $currencyCode, 'last/2');
            } catch (Throwable $e) {
                $this->log->warning('NBP API: Unable to retrieve exchange rates for ' . $currencyCode . ' from table ' . $table, [
                    $e->getMessage(),
                ]);

                continue;
            }

            if ($exchangeRates === null || count($exchangeRates->rates) < 2) {
                $this->log->warning("NBP API: Invalid or incomplete rates array returned for {$currencyCode}");

                continue;
            }

            $rateLatest = $exchangeRates->rates[1];

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
            if ($rateLatest->effectiveDate !== date('Y-m-d')) {
                $index = 1;
            }

            $this->setCurrencyRecordRate($currencyRecord, $exchangeRates->rates[$index]);
        }
    }

    private function setCurrencyRecordRate(CurrencyRecord $currencyRecord, NbpRateItem $rate): void
    {
        $rateDate = Date::createToday();

        $isExisting = $this->entityManager
            ->getRDBRepositoryByClass(CurrencyRecordRate::class)
            ->where([
                CurrencyRecordRate::FIELD_DATE => $rateDate->toString(),
                CurrencyRecordRate::FIELD_BASE_CODE => $this->configDataProvider->getBaseCurrency(),
                CurrencyRecordRate::ATTR_RECORD_ID => $currencyRecord->getId(),
            ])
            ->findOne();

        if ($isExisting) {
            return;
        }

        $currencyRecordRate = $this->entityManager
            ->getRDBRepositoryByClass(CurrencyRecordRate::class)
            ->getNew();

        $currencyRecordRate->setRate((string) $rate->mid);
        $currencyRecordRate->setBaseCode($this->configDataProvider->getBaseCurrency());
        $currencyRecordRate->setRecord($currencyRecord);
        $currencyRecordRate->setDate($rateDate);

        $currencyRecordRate->set('nbpEffectiveDate', $rate->effectiveDate);
        $currencyRecordRate->set('nbpTableNumber', $rate->no);

        $this->entityManager->saveEntity($currencyRecordRate);
    }

    private function getExchangeRateTable(): string
    {
        $table = $this->config->get('nbpExchangeRateTable');

        if (is_string($table) && in_array($table, ['A', 'B', 'C'], true)) {
            return $table;
        }

        return 'A';
    }
}
