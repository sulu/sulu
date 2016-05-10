<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Document\Subscriber;

use PHPCR\NodeInterface;
use PHPCR\NodeType\NodeTypeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\ReorderEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\NodeHelperInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This subscriber handles the live session.
 */
class PublishSubscriber implements EventSubscriberInterface
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var SessionInterface
     */
    private $liveSession;

    /**
     * @var NodeHelperInterface
     */
    private $nodeHelper;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    public function __construct(
        DocumentManagerInterface $documentManager,
        SessionInterface $liveSession,
        NodeHelperInterface $nodeHelper,
        PropertyEncoder $propertyEncoder
    ) {
        $this->documentManager = $documentManager;
        $this->liveSession = $liveSession;
        $this->nodeHelper = $nodeHelper;
        $this->propertyEncoder = $propertyEncoder;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => ['createNodeInPublicWorkspace', -490],
            Events::REMOVE => 'removeNodeFromPublicWorkspace',
            Events::MOVE => 'moveNodeInPublicWorkspace',
            Events::COPY => 'copyNodeInPublicWorkspace',
            Events::REORDER => 'reorderNodeInPublicWorkspace',
            Events::PUBLISH => ['setNodeFromPublicWorkspace', 512],
            Events::FLUSH => 'flushPublicWorkspace',
        ];
    }

    /**
     * Creates the node with the same UUID in the public workspace if it does not exist yet. In case it does it will
     * be renamed if necessary.
     *
     * @param PersistEvent $event
     */
    public function createNodeInPublicWorkspace(PersistEvent $event)
    {
        $node = $event->getNode();

        if ($node->isNew()) {
            $this->createNodesWithUuid($node);

            return;
        }

        $liveNode = $this->getLiveNode($event->getDocument());
        $nodeName = $node->getName();

        if ($liveNode->getName() !== $nodeName) {
            $liveNode->rename($nodeName);
        }
    }

    /**
     * Since deleting is not draftable the node will be deleted in the live session as soon as it is deleted in the
     * default session.
     *
     * @param RemoveEvent $event
     */
    public function removeNodeFromPublicWorkspace(RemoveEvent $event)
    {
        $this->getLiveNode($event->getDocument())->remove();
    }

    /**
     * Since moving is not draftable the node will also be moved in the live session immediately.
     *
     * @param MoveEvent $event
     */
    public function moveNodeInPublicWorkspace(MoveEvent $event)
    {
        $liveNode = $this->getLiveNode($event->getDocument());
        $this->nodeHelper->move($liveNode, $event->getDestId(), $event->getDestName());
    }

    /**
     * If a node is copied a node with the same UUID will be created in the live session.
     *
     * @param CopyEvent $event
     */
    public function copyNodeInPublicWorkspace(CopyEvent $event)
    {
        $this->createNodesWithUuid($event->getCopiedNode());
    }

    /**
     * Reordering is also not draftable, and therefore also immediately applied to the live session.
     *
     * @param ReorderEvent $event
     */
    public function reorderNodeInPublicWorkspace(ReorderEvent $event)
    {
        $node = $event->getNode();

        $this->nodeHelper->reorder($node, $event->getDestId());

        // FIXME duplicating logic of OrderSubscriber, maybe move to NodeHelper?
        $count = 1;
        foreach ($node->getParent()->getNodes() as $childNode) {
            $childNode->setProperty($this->propertyEncoder->systemName('order'), $count * 10);
            ++$count;
        }
    }

    /**
     * Sets the correct node from the live session for the PublishEvent.
     *
     * @param PublishEvent $event
     */
    public function setNodeFromPublicWorkspace(PublishEvent $event)
    {
        $event->setNode($this->getLiveNode($event->getDocument()));
    }

    /**
     * Flushes the live session.
     */
    public function flushPublicWorkspace()
    {
        $this->liveSession->save();
    }

    /**
     * Creates every node on the path to the given node. Also uses the same UUIDs for these nodes.
     *
     * @param NodeInterface $node
     */
    private function createNodesWithUuid(NodeInterface $node)
    {
        $path = $node->getPath();

        if ($this->liveSession->itemExists($path)) {
            return;
        }

        $currentDefaultNode = $node->getSession()->getRootNode();
        $currentLiveNode = $this->liveSession->getRootNode();

        $pathSegments = explode('/', ltrim($path, '/'));
        foreach ($pathSegments as $pathSegment) {
            $currentDefaultNode = $currentDefaultNode->getNode($pathSegment);

            if ($currentLiveNode->hasNode($pathSegment)) {
                $currentLiveNode = $currentLiveNode->getNode($pathSegment);
                continue;
            }

            $currentLiveNode = $currentLiveNode->addNode($pathSegment);
            $currentLiveNode->setMixins(
                array_map(
                    function (NodeTypeInterface $nodeType) {
                        return $nodeType->getName();
                    },
                    $currentDefaultNode->getMixinNodeTypes()
                )
            );
            $currentLiveNode->setProperty('jcr:uuid', $currentDefaultNode->getIdentifier());
        }
    }

    /**
     * Returns the live node for given document.
     *
     * @param PathBehavior $document
     *
     * @return NodeInterface
     */
    private function getLiveNode(PathBehavior $document)
    {
        return $this->liveSession->getNode($document->getPath());
    }
}
