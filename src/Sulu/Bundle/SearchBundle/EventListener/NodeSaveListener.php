<?php

namespace Sulu\Bundle\SearchBundle\EventListener;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Component\Content\Event\ContentNodeEvent;
use Massive\Bundle\SearchBundle\Search\SearchManager;
use Sulu\Component\Content\Structure;

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
     * @var string
     */
    protected $tempName;

    /**
     * @var string
     */
    protected $baseName;

    public function __construct(SearchManagerInterface $searchManager, $baseName, $tempName)
    {
        $this->searchManager = $searchManager;
        $this->tempName = $tempName;
        $this->baseName = $baseName;
    }

    public function onNodeSave(ContentNodeEvent $event)
    {
        $structure = $event->getStructure();
        preg_match('{/' . $this->baseName . '/(.*?)/(.*?)(/.*)*$}', $structure->getPath(), $matches);

        // only if it is none temp node and it is published
        if ($matches[2] !== $this->tempName && $structure->getNodeState() !== Structure::STATE_PUBLISHED) {
            $this->searchManager->index($structure, $structure->getLanguageCode());
        }
    }
}
