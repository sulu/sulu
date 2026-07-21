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
                Assert::integer($fieldDefinition['length'], 'We expect the length to be a integer.');

                $countLimit += $fieldDefinition['length'];
            }

            // InnoDB DYNAMIC row format (MySQL default since 5.7.9) allows a 3072 byte index
            // key prefix; at 4 bytes/char (utf8mb4) that's 768 chars. The old 191-char (767 byte)
            // limit only applies to legacy COMPACT/REDUNDANT row format tables and is enforced
            // separately via LegacyLengthSubscriber for un-migrated installations.
            $this->assertLessThanOrEqual(768, $countLimit, 'The index "' . $indexName . '" exceeds the MySQL utf8mb4 DYNAMIC row format limit.');
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
                Assert::integer($fieldDefinition['length'], 'We expect the length to be a integer.');

                $countLimit += $fieldDefinition['length'];
            }

            // See comment in testMetadataIndexDoNotExceedMySQLUtf8Mb4Limits() re: 768-char DYNAMIC row format budget.
            $this->assertLessThanOrEqual(768, $countLimit, 'The index "' . $uniqueConstraintName . '" exceeds the MySQL utf8mb4 DYNAMIC row format limit.');
        }
    }

    public function testPersistAndLoadRouteWithLegacyFieldLengthsWhenLegacyLengthEnabled(): void
    {
        self::bootKernel(['environment' => 'test_legacy']);

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $classMetadata = $entityManager->getClassMetadata(Route::class);

        $webspaceLength = $classMetadata->getFieldMapping('webspace')['length'];
        $slugLength = $classMetadata->getFieldMapping('slug')['length'];

        Assert::integer($webspaceLength, 'We expect the webspace length to be a integer.');
        Assert::integer($slugLength, 'We expect the slug length to be a integer.');

        $webspace = \str_repeat('w', $webspaceLength);
        $slug = \str_repeat('s', $slugLength);

        $entityManager->getConnection()->executeStatement(
            'DELETE FROM ro_routes WHERE resource_key = ?',
            ['metadata-test'],
        );

        $route = new Route('metadata-test', 'legacy-length-test', 'en', $slug, $webspace);

        $entityManager->persist($route);
        $entityManager->flush();
        $routeId = $route->getId();
        $entityManager->clear();

        try {
            /** @var Route|null $reloadedRoute */
            $reloadedRoute = $entityManager->find(Route::class, $routeId);

            $this->assertNotNull($reloadedRoute, 'We expect the route to be found after reloading it from the database.');
            $this->assertSame($webspace, $reloadedRoute->getWebspace(), 'We expect the webspace to not be truncated when it matches the configured legacy field length.');
            $this->assertSame($slug, $reloadedRoute->getSlug(), 'We expect the slug to not be truncated when it matches the configured legacy field length.');
        } finally {
            $entityManager->getConnection()->executeStatement(
                'DELETE FROM ro_routes WHERE resource_key = ?',
                ['metadata-test'],
            );
        }
    }
}
