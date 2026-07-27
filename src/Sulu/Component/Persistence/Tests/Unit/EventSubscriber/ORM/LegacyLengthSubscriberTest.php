<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\Tests\Unit\EventSubscriber\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Article\Domain\Model\ArticleDimensionContentAdditionalWebspace;
use Sulu\Component\Persistence\EventSubscriber\ORM\LegacyLengthSubscriber;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageDimensionContentNavigationContext;
use Sulu\Route\Domain\Model\Route;

class LegacyLengthSubscriberTest extends TestCase
{
    use ProphecyTrait;

    private LegacyLengthSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new LegacyLengthSubscriber();
    }

    public function testLoadClassMetadataShrinksRouteWebspaceField(): void
    {
        $metadata = new ClassMetadata(Route::class);
        $metadata->mapField([
            'fieldName' => 'webspace',
            'type' => 'string',
            'length' => 64,
        ]);

        $this->subscriber->loadClassMetadata($this->createEvent($metadata));

        $this->assertSame(31, $this->getFieldLength($metadata->fieldMappings['webspace']));
    }

    public function testLoadClassMetadataShrinksRouteSlugFieldForMysql(): void
    {
        $metadata = new ClassMetadata(Route::class);
        $metadata->mapField([
            'fieldName' => 'slug',
            'type' => 'string',
            'length' => 255,
        ]);

        $this->subscriber->loadClassMetadata($this->createEvent($metadata, new MySQLPlatform()));

        $this->assertSame(144, $this->getFieldLength($metadata->fieldMappings['slug']));
    }

    public function testLoadClassMetadataShrinksRouteSlugFieldForPostgres(): void
    {
        $metadata = new ClassMetadata(Route::class);
        $metadata->mapField([
            'fieldName' => 'slug',
            'type' => 'string',
            'length' => 255,
        ]);

        $this->subscriber->loadClassMetadata($this->createEvent($metadata, new PostgreSQLPlatform()));

        $this->assertSame(208, $this->getFieldLength($metadata->fieldMappings['slug']));
    }

    public function testLoadClassMetadataShrinksPageWebspaceKeyField(): void
    {
        $metadata = new ClassMetadata(Page::class);
        $metadata->mapField([
            'fieldName' => 'webspaceKey',
            'type' => 'string',
            'length' => 64,
        ]);

        $this->subscriber->loadClassMetadata($this->createEvent($metadata));

        $this->assertSame(31, $this->getFieldLength($metadata->fieldMappings['webspaceKey']));
    }

    public function testLoadClassMetadataShrinksNavigationContextField(): void
    {
        $metadata = new ClassMetadata(PageDimensionContentNavigationContext::class);
        $metadata->mapField([
            'fieldName' => 'navigationContext',
            'type' => 'string',
            'length' => 64,
        ]);

        $this->subscriber->loadClassMetadata($this->createEvent($metadata));

        $this->assertSame(31, $this->getFieldLength($metadata->fieldMappings['navigationContext']));
    }

    public function testLoadClassMetadataShrinksAdditionalWebspaceField(): void
    {
        $metadata = new ClassMetadata(ArticleDimensionContentAdditionalWebspace::class);
        $metadata->mapField([
            'fieldName' => 'additionalWebspace',
            'type' => 'string',
            'length' => 64,
        ]);

        $this->subscriber->loadClassMetadata($this->createEvent($metadata));

        $this->assertSame(31, $this->getFieldLength($metadata->fieldMappings['additionalWebspace']));
    }

    public function testLoadClassMetadataShrinksTemplateKeyField(): void
    {
        $metadata = new ClassMetadata(PageDimensionContent::class);
        $metadata->mapField([
            'fieldName' => 'templateKey',
            'type' => 'string',
            'length' => 64,
        ]);

        $this->subscriber->loadClassMetadata($this->createEvent($metadata));

        $this->assertSame(31, $this->getFieldLength($metadata->fieldMappings['templateKey']));
    }

    public function testLoadClassMetadataDoesNotShrinkUnrelatedTemplateKeyField(): void
    {
        $metadata = new ClassMetadata(\stdClass::class);
        $metadata->mapField([
            'fieldName' => 'templateKey',
            'type' => 'string',
            'length' => 64,
        ]);

        $this->subscriber->loadClassMetadata($this->createEvent($metadata));

        $this->assertSame(64, $this->getFieldLength($metadata->fieldMappings['templateKey']));
    }

    /**
     * @param array<string, mixed>|FieldMapping $fieldMapping
     */
    private function getFieldLength(array|FieldMapping $fieldMapping): ?int
    {
        if (\is_array($fieldMapping)) {
            $length = $fieldMapping['length'];

            return \is_int($length) ? $length : null;
        }

        return $fieldMapping->length;
    }

    /**
     * @param ClassMetadata<object> $metadata
     */
    private function createEvent(ClassMetadata $metadata, ?AbstractPlatform $platform = null): LoadClassMetadataEventArgs
    {
        $connection = $this->prophesize(Connection::class);
        $connection->getDatabasePlatform()->willReturn($platform ?? new MySQLPlatform());

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->getConnection()->willReturn($connection->reveal());

        $event = $this->prophesize(LoadClassMetadataEventArgs::class);
        $event->getClassMetadata()->willReturn($metadata);
        $event->getEntityManager()->willReturn($entityManager->reveal());

        return $event->reveal();
    }
}
