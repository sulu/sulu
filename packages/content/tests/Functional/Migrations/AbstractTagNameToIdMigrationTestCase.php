<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Functional\Migrations;

use Doctrine\DBAL\Connection;
use Psr\Log\NullLogger;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Infrastructure\Doctrine\Migrations\AbstractTagNameToIdMigration;

abstract class AbstractTagNameToIdMigrationTestCase extends SuluTestCase
{
    protected Connection $connection;

    /**
     * @return class-string<AbstractTagNameToIdMigration>
     */
    abstract protected function getMigrationClass(): string;

    abstract protected function getTable(): string;

    abstract protected function getUuidColumn(): string;

    abstract protected function createContent(): string;

    protected function setUp(): void
    {
        self::bootKernel();
        self::purgeDatabase();
        $this->connection = self::getEntityManager()->getConnection();
    }

    public function testConvertsTagNameArrayToIds(): void
    {
        $mobile = $this->createTagId('mobile');
        $cloud = $this->createTagId('cloud');

        $uuid = $this->seedTemplateData([
            'tags' => ['mobile', 'cloud'],
        ]);

        $this->runMigration();

        self::assertSame([$mobile, $cloud], $this->fetchTemplateData($uuid)['tags']);
    }

    public function testLeavesArrayUntouchedWhenAnyStringDoesNotResolve(): void
    {
        $this->createTagId('mobile');

        $uuid = $this->seedTemplateData([
            'tags' => ['mobile', 'unknown'],
        ]);

        $this->runMigration();

        self::assertSame(['mobile', 'unknown'], $this->fetchTemplateData($uuid)['tags']);
    }

    public function testIsIdempotent(): void
    {
        $mobile = $this->createTagId('mobile');

        $uuid = $this->seedTemplateData([
            'tags' => [$mobile],
        ]);

        $this->runMigration();
        $this->runMigration();

        self::assertSame([$mobile], $this->fetchTemplateData($uuid)['tags']);
    }

    public function testConvertsTagsNestedInBlock(): void
    {
        $mobile = $this->createTagId('mobile');

        $uuid = $this->seedTemplateData([
            'blocks' => [
                ['type' => 'text', 'body' => 'hello'],
                ['type' => 'teaser', 'tags' => ['mobile']],
            ],
        ]);

        $this->runMigration();

        $stored = $this->fetchTemplateData($uuid);
        self::assertIsArray($stored['blocks']);
        self::assertIsArray($stored['blocks'][1]);
        self::assertSame([$mobile], $stored['blocks'][1]['tags']);
    }

    public function testLeavesNonStringArraysUntouched(): void
    {
        $this->createTagId('mobile');

        $uuid = $this->seedTemplateData([
            'numbers' => [1, 2, 3],
            'objects' => [['type' => 'thing']],
        ]);

        $this->runMigration();

        $stored = $this->fetchTemplateData($uuid);
        self::assertSame([1, 2, 3], $stored['numbers']);
        self::assertSame([['type' => 'thing']], $stored['objects']);
    }

    private function runMigration(): void
    {
        $class = $this->getMigrationClass();
        $migration = new $class($this->connection, new NullLogger());
        $migration->up($this->connection->createSchemaManager()->introspectSchema());
    }

    private function createTagId(string $name): int
    {
        $em = self::getEntityManager();
        $tag = new Tag();
        $tag->setName($name);
        $em->persist($tag);
        $em->flush();

        return (int) $tag->getId();
    }

    /**
     * @param array<string, mixed> $templateData
     */
    private function seedTemplateData(array $templateData): string
    {
        $uuid = $this->createContent();

        $this->connection->createQueryBuilder()
            ->update($this->getTable())
            ->set('templateData', ':templateData')
            ->where($this->getUuidColumn() . ' = :uuid')
            ->setParameter('templateData', \json_encode($templateData, \JSON_THROW_ON_ERROR))
            ->setParameter('uuid', $uuid)
            ->executeStatement();

        return $uuid;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function fetchTemplateData(string $uuid): array
    {
        $raw = $this->connection->createQueryBuilder()
            ->select('templateData')
            ->from($this->getTable())
            ->where($this->getUuidColumn() . ' = :uuid')
            ->setParameter('uuid', $uuid)
            ->executeQuery()
            ->fetchOne();
        self::assertIsString($raw);
        $data = \json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        return $data;
    }
}
