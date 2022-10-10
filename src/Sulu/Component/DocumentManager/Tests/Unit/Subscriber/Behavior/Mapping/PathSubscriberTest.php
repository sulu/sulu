<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior\Mapping;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\DocumentInspector;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping\PathSubscriber;

class PathSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<AbstractMappingEvent>
     */
    private $abstractMappingEvent;

    /**
     * @var ObjectProphecy<PathBehavior>
     */
    private $document;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $pathNode;

    /**
     * @var \stdClass
     */
    private $pathDocument;

    /**
     * @var ObjectProphecy<DocumentInspector>
     */
    private $inspector;

    /**
     * @var ObjectProphecy<DocumentAccessor>
     */
    private $accessor;

    /**
     * @var PathSubscriber
     */
    private $pathSubscriber;

    public function setUp(): void
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

    public function testSetInitialPathNotImplementing(): void
    {
        $this->abstractMappingEvent->getDocument()->willReturn(\stdClass::class);
        $this->accessor->set(Argument::cetera())->shouldNotBeCalled();
        $this->pathSubscriber->setInitialPath($this->abstractMappingEvent->reveal());
    }

    public function testSetInitialPath(): void
    {
        $this->abstractMappingEvent->getDocument()->willReturn($this->document->reveal());

        $this->inspector->getPath($this->document->reveal())->willReturn('/path/to');
        $this->accessor->set('path', '/path/to')->shouldBeCalled();

        $this->pathSubscriber->setInitialPath($this->abstractMappingEvent->reveal());
    }

    public function testSetFinalPathNotImplementing(): void
    {
        $this->abstractMappingEvent->getDocument()->willReturn(\stdClass::class);
        $this->accessor->set(Argument::cetera())->shouldNotBeCalled();
        $this->pathSubscriber->setFinalPath($this->abstractMappingEvent->reveal());
    }

    public function testSetFinalPath(): void
    {
        $this->abstractMappingEvent->getDocument()->willReturn($this->document->reveal());

        $this->inspector->getPath($this->document->reveal())->willReturn('/path/to');
        $this->accessor->set('path', '/path/to')->shouldBeCalled();

        $this->pathSubscriber->setFinalPath($this->abstractMappingEvent->reveal());
    }
}
