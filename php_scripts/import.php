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

use Espo\Core\Application;
use Espo\Core\Container;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Crypt;
use Espo\Core\Utils\PasswordHash;
use Espo\Entities\ArrayValue;
use Espo\Entities\Note;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

define('TEST_DATA_PATH', __DIR__ . '/../tests/integration/testData/NbpExchangeRates/InitData.php');

if (!file_exists(TEST_DATA_PATH)) {
    echo 'File with test data not found' . PHP_EOL;
    exit(0);
}

$app = new Application();
$app->setupSystemUser();

(new Import())->run($app->getContainer());

class Import
{
    private Container $container;

    private InjectableFactory $injectableFactory;

    private EntityManager $entityManager;

    private PasswordHash $passwordHash;

    private Crypt $crypt;

    public function run(Container $container): void
    {
        $this->container = $container;
        $this->injectableFactory = $this->container->getByClass(InjectableFactory::class);
        $this->entityManager = $this->container->getByClass(EntityManager::class);
        $this->passwordHash = $this->injectableFactory->create(PasswordHash::class);
        $this->crypt = $this->injectableFactory->create(Crypt::class);

        $this->importData();
    }

    private function importData(): void
    {
        $data = include TEST_DATA_PATH;
        $data = $data['entities'];

        $this->removeAdminUser();

        foreach ($data as $entityType => $collection) {
            $this->importEntityData($entityType, $collection);
        }
    }

    private function removeAdminUser(): void
    {
        $this->entityManager->getQueryExecutor()->execute(
            $this->entityManager
                ->getQueryBuilder()
                ->delete()
                ->from(User::ENTITY_TYPE)
                ->where([
                    'userName' => User::TYPE_ADMIN,
                ])
                ->build(),
        );
    }

    private function importEntityData(string $entityType, array $collection): void
    {
        foreach ($collection as $entityData) {
            $toAppend = $entityData['__APPEND__'] ?? null;
            $entityId = $entityData['id'] ?? null;

            if (!is_string($entityId)) {
                throw new RuntimeException('Entity ID must be a string');
            }

            $entity = null;

            if ($toAppend === true) {
                $entity = $this->entityManager->getRepository($entityType)->getById($entityId);
            } else {
                $this->cleanUpEntity($entityType, $entityId);
            }

            if (!$entity) {
                $entity = $this->entityManager->getRepository($entityType)->getNew();
            }

            $saveOptions = [];

            foreach ($entityData as $field => $value) {
                if ($field === '__SAVE_OPTIONS__') {
                    $saveOptions = $value;

                    continue;
                }

                if ('createdById' === $field) {
                    $saveOptions[$field] = $value;

                    continue;
                }

                if ('password' === $field && $entityType === User::ENTITY_TYPE) {
                    $currentPassword = $entity->get($field) ?? null;

                    if (
                        is_string($currentPassword) &&
                        $this->passwordHash->verify($value, $currentPassword)
                    ) {
                        // Skip if the password is the same
                        continue;
                    }

                    $value = $this->passwordHash->hash($value);
                }

                if (in_array($entityType, ['EmailAccount', 'InboundEmail'], true)) {
                    if ('password' === $field) {
                        $value = $this->crypt->encrypt($value);
                    }

                    if ('smtpPassword' === $field) {
                        $value = $this->crypt->encrypt($value);
                    }
                }

                $entity->set($field, $value);
            }

            $this->entityManager->saveEntity($entity, $saveOptions);
        }
    }

    private function cleanUpEntity(string $entityType, string $entityId): void
    {
        $this->entityManager->getQueryExecutor()->execute(
            $this->entityManager
                ->getQueryBuilder()
                ->delete()
                ->from($entityType)
                ->where([
                    'id' => $entityId,
                ])
                ->build(),
        );

        $this->entityManager->getQueryExecutor()->execute(
            $this->entityManager
                ->getQueryBuilder()
                ->delete()
                ->from(Note::ENTITY_TYPE)
                ->where([
                    'OR' => [
                        [
                            'parentId' => $entityId,
                            'parentType' => $entityType,
                        ],
                        [
                            'superParentId' => $entityId,
                            'superParentType' => $entityType,
                        ],
                        [
                            'relatedId' => $entityId,
                            'relatedType' => $entityType,
                        ],
                    ],
                ])
                ->build(),
        );

        $this->entityManager->getQueryExecutor()->execute(
            $this->entityManager
                ->getQueryBuilder()
                ->delete()
                ->from(ArrayValue::ENTITY_TYPE)
                ->where([
                    'entityType' => $entityType,
                    'entityId' => $entityId,
                ])
                ->build(),
        );
    }
}
