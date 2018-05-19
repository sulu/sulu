<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior\Mapping;

use PHPCR\NodeInterface;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping\PathSubscriber;

class PathSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractMappingEvent
     */
    private $abstractMappingEvent;

    /**
     * @var PathBehavior
     */
    private $document;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var NodeInterface
     */
    private $pathNode;

    /**
     * @var \stdClass
     */
    private $pathDocument;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var DocumentAccessor
     */
    private $accessor;

    /**
     * @var PathSubscriber
     */
    private $pathSubscriber;

    public function setUp()
    {
        $this->abstractMappingEvent = $this->prophesize(AbstractMappingEvent::class);
        $this->document = $this->prophesize(PathBehavior::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->pathNode = $this->prophesize(NodeInterface::class);
        $this->pathDocument = new \stdClass();
        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->accessor = $this->prophesize(DocumentAccessor::class);
        $this->abstractMappingEvent->getAccessor()->willReturn($this->accessor);

        $this->pathSubscriber = new PathSubscriber(
            $this->inspector->reveal()
        );
    }

    public function testSetInitialPathNotImplementing()
    {
        $this->abstractMappingEvent->getDocument()->willReturn(\stdClass::class);
        $this->accessor->set(Argument::cetera())->shouldNotBeCalled();
        $this->pathSubscriber->setInitialPath($this->abstractMappingEvent->reveal());
    }

    public function testSetInitialPath()
    {
        $this->abstractMappingEvent->getDocument()->willReturn($this->document->reveal());

        $this->inspector->getPath($this->document->reveal())->willReturn('/path/to');
        $this->accessor->set('path', '/path/to')->shouldBeCalled();

        $this->pathSubscriber->setInitialPath($this->abstractMappingEvent->reveal());
    }

    public function testSetFinalPathNotImplementing()
    {
        $this->abstractMappingEvent->getDocument()->willReturn(\stdClass::class);
        $this->accessor->set(Argument::cetera())->shouldNotBeCalled();
        $this->pathSubscriber->setFinalPath($this->abstractMappingEvent->reveal());
    }

    public function testSetFinalPath()
    {
        $this->abstractMappingEvent->getDocument()->willReturn($this->document->reveal());

        $this->inspector->getPath($this->document->reveal())->willReturn('/path/to');
        $this->accessor->set('path', '/path/to')->shouldBeCalled();

        $this->pathSubscriber->setFinalPath($this->abstractMappingEvent->reveal());
    }
}
