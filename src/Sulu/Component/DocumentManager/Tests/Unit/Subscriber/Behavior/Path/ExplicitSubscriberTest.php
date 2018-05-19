<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior\Audit\Path;

use PHPCR\ItemExistsException;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Event\ConfigureOptionsEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Path\ExplicitSubscriber;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExplicitSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PersistEvent
     */
    private $persistEvent;

    /**
     * @var \stdClass
     */
    private $document;

    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * @var ConfigureOptionsEvent
     */
    private $configureEvent;

    /**
     * @var NodeInterface
     */
    private $parentNode;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var ExplicitSubscriber
     */
    private $subscriber;

    public function setUp()
    {
        $this->persistEvent = $this->prophesize(PersistEvent::class);
        $this->document = new \stdClass();
        $this->nodeManager = $this->prophesize(NodeManager::class);
        $this->configureEvent = $this->prophesize(ConfigureOptionsEvent::class);
        $this->parentNode = $this->prophesize(NodeInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);

        $this->subscriber = new ExplicitSubscriber(
            $this->nodeManager->reveal()
        );
    }

    /**
     * It should throw an exception if both path name and node_name options are given.
     *
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testExceptionNodeNameAndPath()
    {
        $options = $this->resolveOptions([
            'path' => '/path/to/nodename',
            'node_name' => '/foo',
        ]);
        $this->persistEvent->getOptions()->willReturn($options);
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should throw an exception if both path name and parent_path options are given.
     *
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testExceptionParentPathAndPath()
    {
        $options = $this->resolveOptions([
            'path' => '/path/to/nodename',
            'parent_path' => '/foo',
        ]);
        $this->persistEvent->getOptions()->willReturn($options);
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should set the parent node and create a new node when given a full path.
     */
    public function testNewNodeFromPath()
    {
        $options = $this->resolveOptions(['path' => '/path/to/nodename']);
        $this->nodeManager->find('/path/to')->willReturn($this->parentNode->reveal());
        $this->parentNode->hasNode('nodename')->shouldBeCalled()->willReturn(false);
        $this->parentNode->addNode('nodename')->shouldBeCalled()->willReturn($this->node->reveal());

        $this->persistEvent->getDocument()->willReturn($this->document);
        $this->persistEvent->setParentNode($this->parentNode->reveal())->shouldBeCalled();
        $this->persistEvent->getParentNode()->willReturn($this->parentNode->reveal());
        $this->persistEvent->hasParentNode()->willReturn(true);
        $this->persistEvent->getOptions()->willReturn($options);
        $this->persistEvent->hasNode()->willReturn(false);
        $this->persistEvent->setNode($this->node->reveal())->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should use a new node when override flag is true.
     */
    public function testNewNodeFromPathOverwrite()
    {
        $options = $this->resolveOptions(['path' => '/path/to/nodename', 'override' => true]);
        $this->nodeManager->find('/path/to')->willReturn($this->parentNode->reveal());
        $this->parentNode->hasNode('nodename')->shouldBeCalled()->willReturn(true);
        $this->parentNode->addNode('nodename')->shouldNotBeCalled();
        $this->parentNode->getNode('nodename')->shouldBeCalled()->willReturn($this->node->reveal());

        $this->persistEvent->getDocument()->willReturn($this->document);
        $this->persistEvent->setParentNode($this->parentNode->reveal())->shouldBeCalled();
        $this->persistEvent->getParentNode()->willReturn($this->parentNode->reveal());
        $this->persistEvent->hasParentNode()->willReturn(true);
        $this->persistEvent->getOptions()->willReturn($options);
        $this->persistEvent->hasNode()->willReturn(false);
        $this->persistEvent->setNode($this->node->reveal())->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should throw exception when override flag is false.
     */
    public function testNewNodeFromPathNoOverwrite()
    {
        $this->setExpectedException(
            ItemExistsException::class,
            'The node \'/path/to\' already has a child named \'nodename\'.'
        );

        // override default false
        $options = $this->resolveOptions(['path' => '/path/to/nodename']);
        $this->nodeManager->find('/path/to')->willReturn($this->parentNode->reveal());
        $this->parentNode->hasNode('nodename')->shouldBeCalled()->willReturn(true);
        $this->parentNode->addNode('nodename')->shouldNotBeCalled();
        $this->parentNode->getNode('nodename')->shouldNotBeCalled();
        $this->parentNode->getPath()->willReturn('/path/to');

        $this->persistEvent->getDocument()->willReturn($this->document);
        $this->persistEvent->setParentNode($this->parentNode->reveal())->shouldBeCalled();
        $this->persistEvent->getParentNode()->willReturn($this->parentNode->reveal());
        $this->persistEvent->hasParentNode()->willReturn(true);
        $this->persistEvent->hasNode()->willReturn(false);
        $this->persistEvent->getOptions()->willReturn($options);

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should just set the parent if only the "parent_path" is specified.
     */
    public function testSetParentNode()
    {
        $options = $this->resolveOptions([
            'parent_path' => '/path/to',
        ]);

        $this->nodeManager->find('/path/to')->willReturn($this->parentNode->reveal());

        $this->persistEvent->getDocument()->willReturn($this->document);
        $this->persistEvent->setParentNode($this->parentNode->reveal())->shouldBeCalled();
        $this->persistEvent->getOptions()->willReturn($options);

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should automatically create the parent path if auto_create is specified.
     */
    public function testAutoCreateParent()
    {
        $options = $this->resolveOptions([
            'parent_path' => '/path/to',
            'auto_create' => true,
        ]);

        $this->nodeManager->createPath('/path/to')->willReturn($this->parentNode->reveal());

        $this->persistEvent->getDocument()->willReturn($this->document);
        $this->persistEvent->setParentNode($this->parentNode->reveal())->shouldBeCalled();
        $this->persistEvent->getOptions()->willReturn($options);

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should throw an exception if node_name is specified but no parent node is available.
     *
     * @expectedException \Sulu\Component\DocumentManager\Exception\DocumentManagerException
     */
    public function testNodeNameButNotParentNode()
    {
        $options = $this->resolveOptions([
            'node_name' => 'foobar',
        ]);

        $this->nodeManager->createPath('/path/to')->willReturn($this->parentNode->reveal());

        $this->persistEvent->hasParentNode()->willReturn(false);
        $this->persistEvent->getDocument()->willReturn($this->document);
        $this->persistEvent->getOptions()->willReturn($options);

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should rename the node if the node is already set in the Persist event and
     * the node name is different.
     */
    public function testRename()
    {
        $options = $this->resolveOptions([
            'parent_path' => '/path/to',
            'node_name' => 'booboo',
        ]);

        $this->nodeManager->find('/path/to')->willReturn($this->parentNode->reveal());

        $this->persistEvent->getDocument()->willReturn($this->document);
        $this->persistEvent->setParentNode($this->parentNode->reveal())->shouldBeCalled();
        $this->persistEvent->getOptions()->willReturn($options);
        $this->persistEvent->hasNode()->willReturn(true);
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->persistEvent->hasParentNode()->willReturn(true);
        $this->node->getName()->willReturn('barbar');
        $this->node->rename('booboo')->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should do nothing if none of the options are specified.
     */
    public function testDoNothing()
    {
        $options = $this->resolveOptions([]);

        $this->persistEvent->getOptions()->willReturn($options);
        $this->persistEvent->getDocument()->willReturn($this->document);
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    private function resolveOptions($options)
    {
        $resolver = new OptionsResolver();
        $this->configureEvent->getOptions()->willReturn($resolver);
        $this->subscriber->configureOptions($this->configureEvent->reveal());

        return $resolver->resolve($options);
    }
}
