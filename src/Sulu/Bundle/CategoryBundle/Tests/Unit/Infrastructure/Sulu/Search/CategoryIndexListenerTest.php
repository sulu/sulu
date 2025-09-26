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
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryCreatedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryModifiedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryRemovedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryRestoredEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryTranslationAddedEvent;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation;
use Sulu\Bundle\CategoryBundle\Infrastructure\Sulu\Search\CategoryIndexListener;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[CoversClass(CategoryIndexListener::class)]
class CategoryIndexListenerTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    /**
     * @var ObjectProphecy<MessageBusInterface>
     */
    private ObjectProphecy $messageBus;
    private CategoryIndexListener $listener;

    protected function setUp(): void
    {
        $this->messageBus = $this->prophesize(MessageBusInterface::class);
        $this->listener = new CategoryIndexListener($this->messageBus->reveal());
    }

    public function testOnCategoryChangedWithCategoryCreatedEvent(): void
    {
        $category = new Category();
        $category->setId(123);
        $event = new CategoryCreatedEvent($category, 'en', []);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([CategoryInterface::RESOURCE_KEY . '::123::en']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onCategoryChanged($event);
    }

    public function testOnCategoryChangedWithCategoryModifiedEvent(): void
    {
        $category = new Category();
        $category->setId(456);
        $event = new CategoryModifiedEvent($category, 'en', []);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([CategoryInterface::RESOURCE_KEY . '::456::en']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onCategoryChanged($event);
    }

    public function testOnCategoryChangedWithCategoryRemovedEvent(): void
    {
        $category = new Category();
        $category->setId(789);
        $event = new CategoryRemovedEvent($category->getId(), 'Uncool category', 'en', ['en', 'de']);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([CategoryInterface::RESOURCE_KEY . '::789::en', CategoryInterface::RESOURCE_KEY . '::789::de']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onCategoryChanged($event);
    }

    public function testOnCategoryChangedWithCategoryTranslationAddedEvent(): void
    {
        $category = new Category();
        $category->setId(111);
        $category->setDefaultLocale('en');
        $event = new CategoryTranslationAddedEvent($category, 'de', []);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([CategoryInterface::RESOURCE_KEY . '::111::de']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onCategoryChanged($event);
    }

    public function testOnCategoryChangedWithCategoryRestoredEvent(): void
    {
        $category = new Category();
        $category->setId(111);
        $category->setDefaultLocale('en');
        $categoryTranslation = new CategoryTranslation();
        $categoryTranslation->setLocale('en');
        $category->addTranslation($categoryTranslation);
        $event = new CategoryRestoredEvent($category, []);

        $expectedConfig = ReindexConfig::create()
            ->withIndex('admin')
            ->withIdentifiers([CategoryInterface::RESOURCE_KEY . '::111::en']);

        $this->messageBus->dispatch($expectedConfig)
            ->willReturn(new Envelope($expectedConfig))
            ->shouldBeCalledOnce();

        $this->listener->onCategoryChanged($event);
    }
}
