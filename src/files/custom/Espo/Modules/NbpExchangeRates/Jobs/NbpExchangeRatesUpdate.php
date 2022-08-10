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

namespace Espo\Modules\NbpExchangeRates\Jobs;

use Espo\Core\Job\JobDataLess;
use Espo\Modules\NbpExchangeRates\Service\NbpExchangeRate;

class NbpExchangeRatesUpdate implements JobDataLess
{
    private NbpExchangeRate $nbpExchangeRate;

    public function __construct(NbpExchangeRate $nbpExchangeRate)
    {
        $this->nbpExchangeRate = $nbpExchangeRate;
    }

    public function run(): void
    {
        $this->nbpExchangeRate->updateExchangeRates();
    }
}
