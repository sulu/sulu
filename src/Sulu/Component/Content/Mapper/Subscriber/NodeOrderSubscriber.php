<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper\Subscriber;

use PHPCR\PropertyType;
use Sulu\Component\Content\ContentEvents;
use Sulu\Component\Content\Event\ContentNodeEvent;
use Sulu\Component\Content\Event\ContentNodeOrderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Calculate the vertical position of the saved node relative to its siblings
 * and update the "order" property accordingly. This is used for sorting results
 * obtained from queries.
 */
class NodeOrderSubscriber implements EventSubscriberInterface
{
    const SULU_ORDER = 'sulu:order';

    public static function getSubscribedEvents()
    {
        return array(
            ContentEvents::NODE_PRE_SAVE => 'handleNodeSave',
            ContentEvents::NODE_ORDER => 'handleNodeOrder',
        );
    }

    public function handleNodeSave(ContentNodeEvent $e)
    {
        $node = $e->getNode();

        if ($node->hasProperty(self::SULU_ORDER)) {
            return;
        }

        $parent = $node->getParent();

        $nodeCount = count($parent->getNodes());
        $order = ($nodeCount + 1) * 10;

        $node->setProperty('sulu:order', $order, PropertyType::LONG);
    }

    public function handleNodeOrder(ContentNodeOrderEvent $e)
    {
        $node = $e->getNode();
        $parent = $node->getParent();

        $order = 1;
        foreach ($parent->getNodes() as $childNode) {
            $childNode->setProperty(self::SULU_ORDER, $order * 10, PropertyType::LONG);
            $order++;
        }
    }
}
