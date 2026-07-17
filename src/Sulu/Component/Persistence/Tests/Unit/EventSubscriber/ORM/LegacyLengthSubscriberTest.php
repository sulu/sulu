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

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Article\Domain\Model\ArticleDimensionContentAdditionalWebspace;
use Sulu\Component\Persistence\EventSubscriber\ORM\LegacyLengthSubscriber;
use Sulu\Page\Domain\Model\Page;
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
            'length' => 32,
        ]);

        $this->subscriber->loadClassMetadata($this->createEvent($metadata));

        $this->assertSame(31, $this->getFieldLength($metadata->fieldMappings['webspace']));
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
        $metadata = new ClassMetadata(Page::class);
        $metadata->mapField([
            'fieldName' => 'templateKey',
            'type' => 'string',
            'length' => 64,
        ]);

        $this->subscriber->loadClassMetadata($this->createEvent($metadata));

        $this->assertSame(31, $this->getFieldLength($metadata->fieldMappings['templateKey']));
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
    private function createEvent(ClassMetadata $metadata): LoadClassMetadataEventArgs
    {
        $event = $this->prophesize(LoadClassMetadataEventArgs::class);
        $event->getClassMetadata()->willReturn($metadata);

        return $event->reveal();
    }
}
