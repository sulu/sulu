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
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Persistence\EventSubscriber\ORM\LegacyLengthSubscriber;
use Sulu\Page\Domain\Model\Page;
use Sulu\Route\Domain\Model\Route;

class LegacyLengthSubscriberTest extends TestCase
{
    use ProphecyTrait;

    private LegacyLengthSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new LegacyLengthSubscriber();
    }

    public function testLoadClassMetadataWidensConfiguredLegacyFields(): void
    {
        $metadata = new ClassMetadata(Route::class);
        $metadata->mapField([
            'fieldName' => 'webspace',
            'type' => 'string',
            'length' => 31,
        ]);

        $this->subscriber->loadClassMetadata($this->createEvent($metadata));

        $this->assertSame(32, $metadata->fieldMappings['webspace']->length);
    }

    public function testLoadClassMetadataWidensTemplateKeyField(): void
    {
        $metadata = new ClassMetadata(Page::class);
        $metadata->mapField([
            'fieldName' => 'templateKey',
            'type' => 'string',
            'length' => 31,
        ]);

        $this->subscriber->loadClassMetadata($this->createEvent($metadata));

        $this->assertSame(64, $metadata->fieldMappings['templateKey']->length);
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
