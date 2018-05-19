<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\tests\Unit\Subscriber\Behavior\Path;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Prophecy\Argument;
use Sulu\Component\DocumentManager\Behavior\Path\AutoNameBehavior;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\DocumentManager\NameResolver;
use Sulu\Component\DocumentManager\NodeManager;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Path\AutoNameSubscriber;
use Symfony\Cmf\Bundle\CoreBundle\Slugifier\SlugifierInterface;

class AutoNameSubscriberTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_LOCALE = 'en';

    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    /**
     * @var SlugifierInterface
     */
    private $slugifier;

    /**
     * @var PersistEvent
     */
    private $persistEvent;

    /**
     * @var MoveEvent
     */
    private $moveEvent;

    /**
     * @var AutoNameBehavior
     */
    private $document;

    /**
     * @var \stdClass
     */
    private $parentDocument;

    /**
     * @var NodeInterface
     */
    private $newNode;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var NodeInterface
     */
    private $parentNode;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var \stdClass
     */
    private $parent;

    /**
     * @var NameResolver
     */
    private $resolver;

    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * @var AutoNameSubscriber
     */
    private $subscriber;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var SessionInterface
     */
    private $liveSession;

    public function setUp()
    {
        $this->documentRegistry = $this->prophesize(DocumentRegistry::class);
        $this->slugifier = $this->prophesize(SlugifierInterface::class);
        $this->persistEvent = $this->prophesize(PersistEvent::class);
        $this->moveEvent = $this->prophesize(MoveEvent::class);
        $this->document = $this->prophesize(AutoNameBehavior::class);
        $this->parentDocument = new \stdClass();
        $this->newNode = $this->prophesize(NodeInterface::class);
        $this->node = $this->prophesize(NodeInterface::class);
        $this->parentNode = $this->prophesize(NodeInterface::class);
        $this->metadata = $this->prophesize(Metadata::class);
        $this->parent = new \stdClass();
        $this->documentRegistry->getDefaultLocale()->willReturn(self::DEFAULT_LOCALE);
        $this->resolver = $this->prophesize(NameResolver::class);
        $this->nodeManager = $this->prophesize(NodeManager::class);
        $this->session = $this->prophesize(SessionInterface::class);
        $this->liveSession = $this->prophesize(SessionInterface::class);

        $this->subscriber = new AutoNameSubscriber(
            $this->documentRegistry->reveal(),
            $this->slugifier->reveal(),
            $this->resolver->reveal(),
            $this->nodeManager->reveal(),
            $this->session->reveal(),
            $this->liveSession->reveal()
        );
    }

    /**
     * It should return early if the document is not an instance of AutoName behavior.
     */
    public function testNotInstanceOfAutoName()
    {
        $document = new \stdClass();
        $this->persistEvent->getOption('auto_name')->willReturn(true);
        $this->persistEvent->hasNode()->willReturn(false);
        $this->persistEvent->getDocument()->willReturn($document);
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should throw an exception if the document has no title.
     */
    public function testNoTitle()
    {
        $this->setExpectedException(DocumentManagerException::class);

        $this->persistEvent->hasNode()->willReturn(false);
        $this->document->getTitle()->willReturn(null);
        $this->persistEvent->getOption('auto_name')->willReturn(true);
        $this->persistEvent->getOption('auto_rename')->willReturn(true);
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->persistEvent->getParentNode()->willReturn($this->parentNode->reveal());
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should assign a name based on the documents title.
     */
    public function testAutoName()
    {
        $this->doTestAutoName('hai', 'hai', true, false);
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should not assign a new name, if the option says it is disabled.
     */
    public function testAutoNameWithDisabledOption()
    {
        $this->persistEvent->getOption('auto_name')->willReturn(false);
        $this->persistEvent->getDocument()->willReturn($this->prophesize(AutoNameBehavior::class)->reveal());
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->node->rename(Argument::any())->shouldNotBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should not assign a new name, if a not supported document is passed.
     */
    public function testAutoNameWithNotSupportedDocument()
    {
        $this->persistEvent->getOption('auto_name')->willReturn(false);
        $this->persistEvent->getDocument()->willReturn(new \stdClass());
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->node->rename(Argument::any())->shouldNotBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should rename the node if the document is being saved in the default locale.
     */
    public function testAlreadyHasNode()
    {
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->persistEvent->getLocale()->willReturn(self::DEFAULT_LOCALE);
        $this->doTestAutoName('hai-bye', 'hai-2', false, true);
        $this->node->getParent()->willReturn($this->parentNode->reveal());
        $this->parentNode->getNodeNames()->willReturn(['hai-bye']);
        $this->node->hasNode()->willReturn(true);
        $this->node->getName()->willReturn('foo');

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should not rename the node if the document is being saved a non-default locale.
     */
    public function testAlreadyHasNodeNonDefaultLocale()
    {
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->persistEvent->getLocale()->willReturn('ay');
        $this->doTestAutoName('hai-bye', 'hai-2', false, true);
        $this->node->rename('hai-bye')->shouldNotBeCalled();
        $this->node->hasNode()->willReturn(true);
        $this->node->getName()->willReturn('foo');

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    /**
     * It should ensure there is no confict when moving a node.
     */
    public function testMoveConflict()
    {
        $this->moveEvent->getDocument()->willReturn($this->document);
        $this->moveEvent->getDestId()->willReturn(1234);
        $this->documentRegistry->getNodeForDocument($this->document)->willReturn($this->node->reveal());
        $this->nodeManager->find(1234)->willReturn($this->node->reveal());
        $this->node->getName()->willReturn('foo');
        $this->resolver->resolveName($this->node->reveal(), 'foo')->willReturn('foobar');
        $this->moveEvent->setDestName('foobar')->shouldBeCalled();

        $this->subscriber->handleMove($this->moveEvent->reveal());
    }

    /**
     * It should rename the node.
     */
    public function testRename()
    {
        $this->persistEvent->getOption('auto_name')->willReturn(true);
        $this->persistEvent->getOption('auto_rename')->willReturn(true);
        $this->persistEvent->getOption('auto_name_locale')->willReturn('en');
        $this->persistEvent->hasNode()->willReturn(true);
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->persistEvent->getParentNode()->willReturn($this->parentNode->reveal());
        $this->persistEvent->getLocale()->willReturn('en');
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());

        $this->node->isNew()->willReturn(false);
        $this->node->getIdentifier()->willReturn('123-123-123');
        $this->node->getName()->willReturn('foobar');
        $this->node->getParent()->willReturn($this->parentNode->reveal());

        $this->document->getTitle()->willReturn('Test');
        $this->slugifier->slugify('Test')->willReturn('test');
        $this->resolver->resolveName($this->parentNode->reveal(), 'test', $this->node->reveal(), true)->willReturn('test');

        $liveNode = $this->prophesize(NodeInterface::class);
        $liveNode->getName()->willReturn('foobar');
        $liveNode->getParent()->willReturn($this->parentNode->reveal());
        $this->session->getNodeByIdentifier('123-123-123')->willReturn($this->node->reveal());
        $this->liveSession->getNodeByIdentifier('123-123-123')->willReturn($liveNode->reveal());

        $liveNode->rename('test')->shouldNotBeCalled();
        $this->node->rename('test')->shouldNotBeCalled();
        $this->subscriber->handleScheduleRename($this->persistEvent->reveal());

        $this->documentRegistry->getDocumentForNode($this->node->reveal(), 'en')->willReturn($this->document->reveal());

        $liveNode->rename('test')->shouldBeCalled();
        $this->node->rename('test')->shouldBeCalled();
        $this->subscriber->handleRename();
    }

    /**
     * It should not rename the node for auto_name false.
     */
    public function testRenameAutoNameFalse()
    {
        $this->persistEvent->getOption('auto_name')->willReturn(false);
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());

        $this->node->rename(Argument::cetera())->shouldNotBeCalled();

        $this->subscriber->handleRename($this->persistEvent->reveal());
    }

    /**
     * It should not rename the node for a document which does not implement AutoNameBehavior.
     */
    public function testRenameWrongDocument()
    {
        $this->persistEvent->getOption('auto_name')->willReturn(true);
        $this->persistEvent->getDocument()->willReturn(new \stdClass());

        $this->node->rename(Argument::cetera())->shouldNotBeCalled();

        $this->subscriber->handleRename($this->persistEvent->reveal());
    }

    /**
     * It should not rename the node for not for a locale which is not default.
     */
    public function testRenameNotDefaultLocale()
    {
        $this->persistEvent->getOption('auto_name')->willReturn(true);
        $this->persistEvent->getOption('auto_name_locale')->willReturn('en');
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->persistEvent->getLocale()->willReturn('de');

        $this->node->rename(Argument::cetera())->shouldNotBeCalled();

        $this->subscriber->handleRename($this->persistEvent->reveal());
    }

    /**
     * It should not rename the node if no node isset.
     */
    public function testRenameForNoNode()
    {
        $this->persistEvent->getOption('auto_name')->willReturn(true);
        $this->persistEvent->getOption('auto_name_locale')->willReturn('en');
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->persistEvent->getLocale()->willReturn('en');
        $this->persistEvent->hasNode()->willReturn(false);

        $this->node->rename(Argument::cetera())->shouldNotBeCalled();

        $this->subscriber->handleRename($this->persistEvent->reveal());
    }

    /**
     * It should not rename the node if node is new.
     */
    public function testRenameForNewNode()
    {
        $this->persistEvent->getOption('auto_name')->willReturn(true);
        $this->persistEvent->getOption('auto_name_locale')->willReturn('en');
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->persistEvent->getLocale()->willReturn('en');
        $this->persistEvent->hasNode()->willReturn(true);
        $this->persistEvent->getNode()->willReturn($this->node->reveal());
        $this->node->isNew()->willReturn(true);

        $this->node->rename(Argument::cetera())->shouldNotBeCalled();

        $this->subscriber->handleRename($this->persistEvent->reveal());
    }

    private function doTestAutoName($title, $expectedName, $create = false, $hasNode = false, $slugifiedName = null)
    {
        $slugifiedName = $slugifiedName ?: $title;

        $this->persistEvent->getOption('auto_name')->willReturn(true);
        $this->persistEvent->getOption('auto_rename')->willReturn(true);
        $this->persistEvent->hasNode()->willReturn($hasNode);
        $node = $hasNode ? $this->node->reveal() : null;

        $this->document->getTitle()->willReturn($title);
        $this->document->getParent()->willReturn($this->parent);
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->slugifier->slugify($title)->willReturn($title);

        $this->resolver->resolveName($this->parentNode->reveal(), $slugifiedName, $node, true)->willReturn($slugifiedName);
        $this->persistEvent->getParentNode()->willReturn($this->parentNode->reveal());

        if (!$create) {
            return;
        }

        $this->parentNode->addNode($expectedName)->shouldBeCalled()->willReturn($this->newNode->reveal());
        $this->persistEvent->setNode($this->newNode->reveal())->shouldBeCalled();
    }
}
