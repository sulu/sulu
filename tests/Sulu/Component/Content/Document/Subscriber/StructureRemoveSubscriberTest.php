<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber;

use PHPCR\NodeInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StructureRemoveSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    /**
     * @var object
     */
    private $document;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var StructureRemoveSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->inspector = $this->prophesize(DocumentInspector::class);
        $this->metadataFactory = $this->prophesize(MetadataFactoryInterface::class);
        $this->document = $this->prophesize();
        $this->node = $this->prophesize(NodeInterface::class);

        $this->subscriber = new StructureRemoveSubscriber(
            $this->documentManager->reveal(),
            $this->inspector->reveal(),
            $this->metadataFactory->reveal()
        );
    }

    public function testConfigureOptions()
    {
        $options = $this->prophesize(OptionsResolver::class);
        $options->setDefault('dereference', false)->shouldBeCalled();
        $options->addAllowedTypes('dereference', 'bool')->shouldBeCalled();

        $configureOptionsEvent = $this->prophesize(ConfigureOptionsEvent::class);
        $configureOptionsEvent->getOptions()->willReturn($options);

        $this->subscriber->configureOptions($configureOptionsEvent->reveal());
    }

    public function testHandleRemoveWithDereference()
    {
        $this->document->willImplement(StructureBehavior::class);
        $this->document->willImplement(ChildrenBehavior::class);
        $this->document->getChildren()->willReturn([])->shouldBeCalled();

        $this->inspector->getNode($this->document->reveal())->willReturn($this->node);
        $this->inspector->getReferrers($this->document->reveal())->willReturn([])->shouldBeCalled();
        $this->node->getReferences()->willReturn([])->shouldBeCalled();

        $removeEvent = $this->prophesize(RemoveEvent::class);
        $removeEvent->getOption('dereference')->willReturn(true);
        $removeEvent->getDocument()->willReturn($this->document);

        $this->subscriber->handleRemove($removeEvent->reveal());
    }

    public function testHandleRemoveWithoutDereference()
    {
        $this->document->willImplement(StructureBehavior::class);
        $this->document->willImplement(ChildrenBehavior::class);
        $this->document->getChildren()->shouldNotBeCalled();

        $this->inspector->getNode($this->document->reveal())->willReturn($this->node);
        $this->node->getReferences()->shouldNotBeCalled();

        $removeEvent = $this->prophesize(RemoveEvent::class);
        $removeEvent->getOption('dereference')->willReturn(false);
        $removeEvent->getDocument()->shouldNotBeCalled();

        $this->subscriber->handleRemove($removeEvent->reveal());
    }
}
