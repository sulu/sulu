<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Comonent\DocumentManager\Tests\Unit\Subscriber;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Sulu\Component\DocumentManager\NodeManager;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Subscriber\Phpcr\FindSubscriber;
use Sulu\Component\DocumentManager\MetadataFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Event\FindEvent;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;

class FindSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->nodeManager = $this->prophesize(NodeManager::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->metadataFactory = $this->prophesize(MetadataFactory::class);
        $this->metadata = $this->prophesize(Metadata::class);
        $this->document = new \stdClass();
        $this->subscriber = new FindSubscriber(
            $this->metadataFactory->reveal(),
            $this->nodeManager->reveal(),
            $this->eventDispatcher->reveal()
        );
    }

    /**
     * It should find an existing document
     */
    public function testFind()
    {
        $this->doTestFind();
    }

    public function provideFindWithTypeOrClass()
    {
        return array(
            array('alias', 'page', false),
            array('class', 'stdClass', false),
            array('class', 'SomeUnknownClass', true),
        );
    }

    /**
     * It should find existing document of specified type or class
     *
     * @dataProvider provideFindWithTypeOrClass
     */
    public function testFindWithTypeOrClass($type, $typeOrClass, $shouldThrow)
    {
        if ($shouldThrow) {
            $this->setExpectedException(DocumentManagerException::class);
        }
        if ($type === 'alias') {
            $this->metadataFactory->hasAlias($typeOrClass)->willReturn(true);
            $this->metadataFactory->getMetadataForAlias($typeOrClass)->willReturn($this->metadata);
            $this->metadata->getClass()->willReturn('stdClass');
        }

        $this->doTestFind($typeOrClass);
    }

    private function doTestFind($typeOrClass = null)
    {
        $locale = 'fr';
        $path = '/path/to';

        $this->nodeManager->find($path)->willReturn($this->node->reveal());
        $this->eventDispatcher
            ->dispatch(Events::HYDRATE, Argument::type(HydrateEvent::class))
            ->will(function ($args) {
                $args[1]->setDocument(new \stdClass());
            });

        $event = new FindEvent($path, $locale, $typeOrClass);
        $this->subscriber->handleFind($event);
        $this->assertInstanceOf('stdClass', $event->getDocument());
    }
}
