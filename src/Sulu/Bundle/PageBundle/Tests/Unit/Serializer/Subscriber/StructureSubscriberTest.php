<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Unit\Serializer\Subscriber;

use JMS\Serializer\AbstractVisitor;
use JMS\Serializer\Context;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\PageBundle\Serializer\Subscriber\StructureSubscriber;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class StructureSubscriberTest extends TestCase
{
    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var StructureSubscriber
     */
    private $structureSubscriber;

    public function setUp(): void
    {
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->structureSubscriber = new StructureSubscriber(
            $this->documentInspector->reveal(),
            $this->webspaceManager->reveal()
        );
    }

    public function testPostSerialize()
    {
        $objectEvent = $this->prophesize(ObjectEvent::class);

        $context = $this->prophesize(Context::class);
        $objectEvent->getContext()->willReturn($context->reveal());

        $document = $this->prophesize(StructureBehavior::class);
        $objectEvent->getObject()->willReturn($document->reveal());

        $visitor = $this->getMockBuilder(AbstractVisitor::class)
            ->setMethods(['visitProperty', 'getResult'])
            ->getMock();
        $objectEvent->getVisitor()->willReturn($visitor);

        $structureMetadata = new StructureMetadata();
        $this->documentInspector->getStructureMetadata($document->reveal())->willReturn($structureMetadata);

        $this->documentInspector->getLocale($document->reveal())->willReturn('en');

        $document->getStructureType()->willReturn('structure');

        $visitor->expects($this->atLeast(2))
            ->method('visitProperty')
            ->withConsecutive(
                [$this->anything(), 'structure'],
                [$this->anything(), 'structure']
            );

        $context->hasAttribute(Argument::any())->willReturn(false);

        $this->structureSubscriber->onPostSerialize($objectEvent->reveal());
    }

    public function testPostSerializeWithExcludedTemplate()
    {
        $objectEvent = $this->prophesize(ObjectEvent::class);

        $context = $this->prophesize(Context::class);
        $objectEvent->getContext()->willReturn($context->reveal());

        $document = $this->prophesize(StructureBehavior::class);
        $document->willImplement(WebspaceBehavior::class);
        $objectEvent->getObject()->willReturn($document->reveal());

        $visitor = $this->getMockBuilder(AbstractVisitor::class)
            ->setMethods(['visitProperty', 'getResult'])
            ->getMock();
        $objectEvent->getVisitor()->willReturn($visitor);

        $structureMetadata = new StructureMetadata();
        $this->documentInspector->getStructureMetadata($document->reveal())->willReturn($structureMetadata);

        $this->documentInspector->getLocale($document->reveal())->willReturn('en');
        $this->documentInspector->getWebspace($document->reveal())->willReturn('sulu');

        $document->getStructureType()->willReturn('structure');

        $webspace = new Webspace();
        $webspace->addExcludedTemplate('structure');
        $this->webspaceManager->findWebspaceByKey('sulu')->willReturn($webspace);

        $visitor->expects($this->exactly(1))
            ->method('visitProperty')
            ->with($this->anything(), false);

        $context->hasAttribute(Argument::any())->willReturn(false);

        $this->structureSubscriber->onPostSerialize($objectEvent->reveal());
    }
}
