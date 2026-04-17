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

use Espo\Core\Container;
use Espo\Core\DataManager;
use Espo\Core\InjectableFactory;
use Espo\Core\Job\JobSchedulerFactory;
use Espo\Core\Job\QueueName;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\ScheduledJob;
use Espo\Modules\NbpExchangeRates\Jobs\NbpExchangeRatesUpdate;

class AfterInstall
{
    private Container $container;

    private InjectableFactory $injectableFactory;

    public function run(Container $container): void
    {
        $this->container = $container;
        $this->injectableFactory = $container->getByClass(InjectableFactory::class);

        $this->doRun();
        $this->clearCache();
    }

    private function doRun(): void
    {
        $entityManager = $this->getEntityManager();

        $job = $entityManager
            ->getRDBRepositoryByClass(ScheduledJob::class)
            ->where([
                'job' => 'NbpExchangeRatesUpdate',
            ])
            ->findOne();

        if (!$job) {
            $job = $entityManager->getRDBRepositoryByClass(ScheduledJob::class)->getNew();

            $job->set([
                'name' => 'NBP Exchange Rates Update',
                'job' => 'NbpExchangeRatesUpdate',
                'status' => ScheduledJob::STATUS_ACTIVE,
                'scheduling' => '0 0-2 * * *',
            ]);

            $entityManager->saveEntity($job);
        }

        $this->injectableFactory
            ->create(JobSchedulerFactory::class)
            ->create()
            ->setClassName(NbpExchangeRatesUpdate::class)
            ->setQueue(QueueName::M0)
            ->schedule();
    }

    private function getEntityManager(): EntityManager
    {
        return $this->container->getByClass(EntityManager::class);
    }

    private function clearCache(): void
    {
        try {
            $this->container->getByClass(DataManager::class)->clearCache();
        } catch (Exception $e) {
        }
    }
}
