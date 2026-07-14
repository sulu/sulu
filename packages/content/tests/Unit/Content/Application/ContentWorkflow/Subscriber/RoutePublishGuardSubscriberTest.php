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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentWorkflow\Subscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Content\Application\ContentWorkflow\Subscriber\RoutePublishGuardSubscriber;
use Sulu\Content\Domain\Exception\PublishWithoutRouteException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Route\Domain\Model\Route;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;

class RoutePublishGuardSubscriberTest extends TestCase
{
    use ProphecyTrait;

    protected function createRoutePublishGuardSubscriberInstance(TypedFormMetadata $typedFormMetadata): RoutePublishGuardSubscriber
    {
        $container = new Container();
        $container->set('form', new class($typedFormMetadata) implements MetadataProviderInterface {
            public function __construct(private readonly TypedFormMetadata $typedFormMetadata)
            {
            }

            public function getMetadata(string $key, string $locale, array $metadataOptions): TypedFormMetadata
            {
                return $this->typedFormMetadata;
            }
        });
        $metadataProviderRegistry = new MetadataProviderRegistry($container);

        return new RoutePublishGuardSubscriber($metadataProviderRegistry);
    }

    private function createGuardEvent(object $subject): GuardEvent
    {
        return new GuardEvent(
            $subject,
            new Marking(),
            new Transition('publish', 'draft', 'published')
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $subscriber = $this->createRoutePublishGuardSubscriberInstance(new TypedFormMetadata());

        $this->assertSame([
            'workflow.content_workflow.guard.publish' => 'guardPublish',
        ], $subscriber::getSubscribedEvents());
    }

    public function testGuardPublishBlocksWhenNoRouteAndRouteFieldExists(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $dimensionContent->setTemplateKey('default');
        $dimensionContent->setLocale('en');

        $event = $this->createGuardEvent($dimensionContent);

        $subscriber = $this->createRoutePublishGuardSubscriberInstance($this->createTypedFormMetadataWithRoute());
        $subscriber->guardPublish($event);

        $this->assertTrue($event->isBlocked());
        $this->assertTrue($event->getTransitionBlockerList()->has(PublishWithoutRouteException::TRANSITION_BLOCKER_CODE));
    }

    public function testGuardPublishDoesNotBlockWhenRouteIsSet(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $dimensionContent->setTemplateKey('default');
        $dimensionContent->setLocale('en');
        $dimensionContent->setRoute(new Route(Example::RESOURCE_KEY, '1', 'en', '/test', null, null));

        $event = $this->createGuardEvent($dimensionContent);

        $subscriber = $this->createRoutePublishGuardSubscriberInstance($this->createTypedFormMetadataWithRoute());
        $subscriber->guardPublish($event);

        $this->assertFalse($event->isBlocked());
    }

    public function testGuardPublishDoesNotBlockShadow(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $dimensionContent->setTemplateKey('default');
        $dimensionContent->setLocale('en');
        $dimensionContent->setShadowLocale('de');

        $event = $this->createGuardEvent($dimensionContent);

        $subscriber = $this->createRoutePublishGuardSubscriberInstance($this->createTypedFormMetadataWithRoute());
        $subscriber->guardPublish($event);

        $this->assertFalse($event->isBlocked());
    }

    public function testGuardPublishDoesNotBlockWhenNoRouteField(): void
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $dimensionContent->setTemplateKey('default');
        $dimensionContent->setLocale('en');

        $event = $this->createGuardEvent($dimensionContent);

        $subscriber = $this->createRoutePublishGuardSubscriberInstance($this->createTypedFormMetadataWithTextLine());
        $subscriber->guardPublish($event);

        $this->assertFalse($event->isBlocked());
    }

    public function testGuardPublishDoesNotBlockWhenRouteIsNotMandatory(): void
    {
        $example = new Example();
        $dimensionContent = new class($example) extends ExampleDimensionContent {
            public static function isRouteMandatory(): bool
            {
                return false;
            }
        };
        $dimensionContent->setTemplateKey('default');
        $dimensionContent->setLocale('en');

        $event = $this->createGuardEvent($dimensionContent);

        $subscriber = $this->createRoutePublishGuardSubscriberInstance($this->createTypedFormMetadataWithRoute());
        $subscriber->guardPublish($event);

        $this->assertFalse($event->isBlocked());
    }

    public function testGuardPublishDoesNotBlockWhenNotRoutable(): void
    {
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);

        $event = $this->createGuardEvent($dimensionContent->reveal());

        $subscriber = $this->createRoutePublishGuardSubscriberInstance(new TypedFormMetadata());
        $subscriber->guardPublish($event);

        $this->assertFalse($event->isBlocked());
    }

    private function createTypedFormMetadataWithRoute(string $propertyName = 'url'): TypedFormMetadata
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setTitle('Default', 'en');
        $formMetadata->setKey('default');

        $routeProperty = new FieldMetadata($propertyName);
        $routeProperty->setMultilingual(true);
        $routeProperty->setType('route');

        $formMetadata->addItem($routeProperty);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm($formMetadata->getKey(), $formMetadata);
        $typedFormMetadata->setDefaultType('default');

        return $typedFormMetadata;
    }

    private function createTypedFormMetadataWithTextLine(string $propertyName = 'title'): TypedFormMetadata
    {
        $formMetadata = new FormMetadata();
        $formMetadata->setTitle('Default', 'en');
        $formMetadata->setKey('default');

        $textLineProperty = new FieldMetadata($propertyName);
        $textLineProperty->setMultilingual(true);
        $textLineProperty->setType('text_line');

        $formMetadata->addItem($textLineProperty);

        $typedFormMetadata = new TypedFormMetadata();
        $typedFormMetadata->addForm($formMetadata->getKey(), $formMetadata);
        $typedFormMetadata->setDefaultType('default');

        return $typedFormMetadata;
    }
}
