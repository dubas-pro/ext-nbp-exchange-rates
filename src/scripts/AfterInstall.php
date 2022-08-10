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

use Espo\Core\Container;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Entities\ScheduledJob;

class AfterInstall
{
    private Container $container;

    public function run(Container $container): void
    {
        $this->container = $container;
        $this->doRun();
        $this->clearCache();
    }

    private function doRun(): void
    {
        $entityManager = $this->getEntityManager();

        $job = $entityManager
            ->getRDBRepository(ScheduledJob::ENTITY_TYPE)
            ->where([
                'job' => 'NbpExchangeRatesUpdate',
            ])
            ->findOne();

        if (!$job) {
            $job = $entityManager->getEntity(ScheduledJob::ENTITY_TYPE);

            $job->set([
                'name' => 'NBP Exchange Rates Update',
                'job' => 'NbpExchangeRatesUpdate',
                'status' => 'Active',
                'scheduling' => '15,45 0-1,11-12 * * 1-5',
            ]);

            $entityManager->saveEntity($job);
        }
    }

    private function getEntityManager(): EntityManager
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->container->get('entityManager');

        return $entityManager;
    }

    private function clearCache(): void
    {
        try {
            /** @var \Espo\Core\DataManager $dataManager */
            $dataManager = $this->container->get('dataManager');
            $dataManager->clearCache();
        } catch (\Exception $e) {
        }
    }
}
