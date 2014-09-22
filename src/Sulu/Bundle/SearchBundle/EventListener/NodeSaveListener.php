<?php

namespace Sulu\Bundle\SearchBundle\EventListener;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Bundle\SearchBundle\LocalizedSearchManager\LocalizedSearchManagerInterface;
use Sulu\Component\Content\Event\ContentNodeEvent;
use Massive\Bundle\SearchBundle\Search\SearchManager;
use Sulu\Component\Content\Structure;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

/**
 * Listen to sulu node save event and index the structure
 */
class NodeSaveListener
{
    /**
     * @var LocalizedSearchManagerInterface
     */
    protected $searchManager;

    /**
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var string
     */
    protected $tempName;

    /**
     * @var string
     */
    protected $baseName;

    public function __construct(SearchManagerInterface $searchManager, SessionManagerInterface $sessionManager, $baseName, $tempName)
    {
        $this->searchManager = $searchManager;
        $this->sessionManager = $sessionManager;
        $this->tempName = $tempName;
        $this->baseName = $baseName;
    }

    public function onNodeSave(ContentNodeEvent $event)
    {
        $structure = $event->getStructure();
        $path = $this->sessionManager->getSession()->getNodeByIdentifier($structure->getUuid())->getPath();

        // TODO: Move logic to determine if node is preview to somewhere else ...
        preg_match('{/' . $this->baseName . '/(.*?)/(.*?)(/.*)*$}', $path, $matches);

        // only if it is none temp node and it is published
        if ($matches[2] !== $this->tempName && $structure->getNodeState() === Structure::STATE_PUBLISHED) {
            $this->searchManager->index($structure);
        }
    }
}
