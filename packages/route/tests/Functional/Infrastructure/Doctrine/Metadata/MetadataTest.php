<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Functional\Infrastructure\Doctrine\Metadata;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Route\Domain\Model\Route;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Webmozart\Assert\Assert;

#[CoversNothing]
class MetadataTest extends KernelTestCase
{
    public function testMetadataIndexDoNotExceedMySQLUtf8Mb4Limits(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $classMetadata = $entityManager->getClassMetadata(Route::class);

        $tableDefinition = $classMetadata->table;

        Assert::true(isset($tableDefinition['indexes']), 'We expect that the table definition contains indexes.');
        Assert::notEmpty($tableDefinition['indexes'], 'We expect that the table definition contains indexes.');

        foreach ($tableDefinition['indexes'] as $indexName => $indexDefinition) {
            Assert::isArray($indexDefinition, 'We expect that the index definition is an array.');
            Assert::true(isset($indexDefinition['fields']), 'We expect that the index definition contains fields.');
            Assert::isArray($indexDefinition['fields'], 'We expect that the index definition contains fields.');
            Assert::notEmpty($indexDefinition['fields'], 'We expect that the index definition contains fields.');

            $countLimit = 0;

            foreach ($indexDefinition['fields'] as $field) {
                Assert::string($field);
                $fieldDefinition = $classMetadata->getFieldMapping($field);

                Assert::true(isset($fieldDefinition['length']), 'We expect the length to be returned.');

                $countLimit += $fieldDefinition['length'];
            }

            $this->assertLessThanOrEqual(191, $countLimit, 'The index "' . $indexName . '" exceeds the MySQL utf8mb4 limit.');
        }
    }

    public function testMetadataUniqueConstraintsDoNotExceedMySQLUtf8Mb4Limits(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $classMetadata = $entityManager->getClassMetadata(Route::class);

        $tableDefinition = $classMetadata->table;

        Assert::true(isset($tableDefinition['uniqueConstraints']), 'We expect that the table definition contains uniqueConstraints.');
        Assert::notEmpty($tableDefinition['uniqueConstraints'], 'We expect that the table definition contains uniqueConstraints.');

        foreach ($tableDefinition['uniqueConstraints'] as $uniqueConstraintName => $uniqueConstraintDefinition) {
            Assert::isArray($uniqueConstraintDefinition, 'We expect that the uniqueConstraints definition is an array.');
            Assert::true(isset($uniqueConstraintDefinition['fields']), 'We expect that the uniqueConstraints definition contains fields.');
            Assert::isArray($uniqueConstraintDefinition['fields'], 'We expect that the uniqueConstraints definition contains fields.');
            Assert::notEmpty($uniqueConstraintDefinition['fields'], 'We expect that the uniqueConstraints definition contains fields.');

            $countLimit = 0;

            foreach ($uniqueConstraintDefinition['fields'] as $field) {
                Assert::string($field);
                $fieldDefinition = $classMetadata->getFieldMapping($field);

                Assert::true('string' === $fieldDefinition['type'], 'Currently this tests handles only strings.');
                Assert::true(isset($fieldDefinition['length']), 'We expect the length to be returned.');

                $countLimit += $fieldDefinition['length'];
            }

            $this->assertLessThanOrEqual(191, $countLimit, 'The index "' . $uniqueConstraintName . '" exceeds the MySQL utf8mb4 limit.');
        }
    }
}
