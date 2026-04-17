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

include '../site/bootstrap.php';

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Config\ConfigWriter;

$app = new \Espo\Core\Application();
$app->setupSystemUser();

$configWriter = $app->getContainer()->getByClass(InjectableFactory::class)->create(ConfigWriter::class);

if (file_exists('../config.php')) {
    $override = include('../config.php');

    foreach ($override as $key => $value) {
        $configWriter->set($key, $value);
    }

    $configWriter->save();
}
