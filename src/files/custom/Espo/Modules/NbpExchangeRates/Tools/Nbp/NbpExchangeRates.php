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

readonly class NbpExchangeRates
{
    /**
     * @param NbpRateItem[] $rates
     */
    public function __construct(
        public array $rates
    ) {}

    public static function fromRaw(mixed $data): ?self
    {
        if (!is_object($data) || !property_exists($data, 'rates') || !is_array($data->rates)) {
            return null;
        }

        $rates = [];
        foreach ($data->rates as $rate) {
            if (
                !is_object($rate) ||
                !property_exists($rate, 'effectiveDate') ||
                !property_exists($rate, 'mid') ||
                !property_exists($rate, 'no') ||
                !is_string($rate->effectiveDate) || !is_string($rate->no) ||
                !is_float($rate->mid) && !is_int($rate->mid) // Accept integers as well since JSON numbers can be either.
            ) {
                // If any rate item is malformed, the whole payload is unreliable.
                return null;
            }

            $rates[] = new NbpRateItem(
                (string) $rate->effectiveDate,
                (float) $rate->mid,
                (string) $rate->no
            );
        }

        return new self($rates);
    }
}
