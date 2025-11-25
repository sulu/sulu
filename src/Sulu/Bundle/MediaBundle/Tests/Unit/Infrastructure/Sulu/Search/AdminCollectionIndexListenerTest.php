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

namespace Sulu\Bundle\CategoryBundle\Tests\Unit\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Domain\Event\CollectionCreatedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\CollectionModifiedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\CollectionRemovedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\CollectionRestoredEvent;
use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\CollectionMeta;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Search\AdminCollectionIndexListener;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(AdminCollectionIndexListener::class)]
class AdminCollectionIndexListenerTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<MessageBusInterface>
     */
    private ObjectProphecy $messageBus;
    private AdminCollectionIndexListener $listener;

    protected function setUp(): void
    {
        $this->messageBus = $this->prophesize(MessageBusInterface::class);
        $this->listener = new AdminCollectionIndexListener($this->messageBus->reveal());
    }

    public function testOnCollectionChangedWithCollectionCreatedEvent(): void
    {
        $collection = $this->createCollection();
        static::setPrivateProperty($collection, 'id', 1);
        $event = new CollectionCreatedEvent($collection, 'en', []);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([CollectionInterface::RESOURCE_KEY . '__' . $collection->getId() . '__en']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onCollectionChanged($event);
    }

    public function testOnCollectionChangedWithCollectionModifiedEvent(): void
    {
        $collection = $this->createCollection();
        static::setPrivateProperty($collection, 'id', 1);
        $event = new CollectionModifiedEvent($collection, 'en', []);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([CollectionInterface::RESOURCE_KEY . '__' . $collection->getId() . '__en']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onCollectionChanged($event);
    }

    public function testOnCollectionChangedWithCollectionRemovedEvent(): void
    {
        $collection = $this->createCollection(['title' => 'test-collection']);
        static::setPrivateProperty($collection, 'id', 1);
        $event = new CollectionRemovedEvent($collection->getId(), 'test-collection', 'en', ['locales' => ['en', 'de']]);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([CollectionInterface::RESOURCE_KEY . '__' . $collection->getId() . '__en', CollectionInterface::RESOURCE_KEY . '__' . $collection->getId() . '__de']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onCollectionChanged($event);
    }

    public function testOnCollectionChangedWithCollectionRestoredEvent(): void
    {
        $collection = $this->createCollection();
        static::setPrivateProperty($collection, 'id', 1);
        $event = new CollectionRestoredEvent($collection, []);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([CollectionInterface::RESOURCE_KEY . '__' . $collection->getId() . '__en']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onCollectionChanged($event);
    }

    /**
     * @param array{
     *     typeName?: string,
     *     title?: string,
     *     locale?: string,
     * } $data
     */
    protected function createCollection(array $data = []): Collection
    {
        $data = \array_merge([
            'typeName' => 'Collection Type',
            'title' => 'Test Collection',
            'locale' => 'en',
        ], $data);
        $collection = new Collection();

        $collectionType = new CollectionType();
        $collectionType->setName($data['typeName']);

        $collection->setType($collectionType);

        $collectionMeta = new CollectionMeta();
        $collectionMeta->setTitle($data['title']);
        $collectionMeta->setLocale($data['locale']);
        $collectionMeta->setCollection($collection);

        $collection->addMeta($collectionMeta);

        return $collection;
    }
}
