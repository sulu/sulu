<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\EventListener;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Component\Content\Event\ContentNodeEvent;
use Sulu\Component\Content\Structure;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

/**
 * Listen to sulu node save event and index the structure
 */
class NodeSaveListener
{
    /**
     * @var SearchManagerInterface
     */
    protected $searchManager;

    /**
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var string
     */
    protected $baseName;

    public function __construct(
        SearchManagerInterface $searchManager,
        SessionManagerInterface $sessionManager,
        $baseName
    )
    {
        $this->searchManager = $searchManager;
        $this->sessionManager = $sessionManager;
        $this->baseName = $baseName;
    }

    public function onNodeSave(ContentNodeEvent $event)
    {
        $structure = $event->getStructure();

        if ($structure->getNodeState() === Structure::STATE_PUBLISHED) {
            $this->searchManager->index($structure);
        } else {
            $this->searchManager->deindex($structure);
        }
    }
}
