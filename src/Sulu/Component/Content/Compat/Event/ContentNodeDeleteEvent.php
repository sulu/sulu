<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Event;

use PHPCR\NodeInterface;
use Sulu\Component\Content\StructureInterface;
use Symfony\Component\EventDispatcher\Event;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Util\SuluNodeHelper;

/**
 * This event is thrown when a node is deleted
 */
class ContentNodeDeleteEvent extends Event
{
    /**
     * @var Sulu\Component\Content\Mapper\ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var PHPCR\NodeInterface
     */
    private $node;

    /**
     * @var string
     */
    private $webspace;

    /**
     * @var Sulu\Component\Util\SuluNodeHelper
     */
    private $nodeHelper;

    /**
     * @param ContentMapperInterface $contentMapper
     * @param SuluNodeHelper $nodeHelper
     * @param NodeInterface $node
     * @param string $webspace
     */
    public function __construct(
        ContentMapperInterface $contentMapper,
        SuluNodeHelper $nodeHelper,
        NodeInterface $node,
        $webspace
    )
    {
        $this->contentMapper = $contentMapper;
        $this->node = $node;
        $this->webspace = $webspace;
        $this->nodeHelper = $nodeHelper;
    }

    /**
     * Return the structure which was deleted
     *
     * @return StructureInterface
     */
    public function getStructure($locale)
    {
        return $this->contentMapper->loadShallowStructureByNode($this->node, $locale, $this->webspace);
    }

    /**
     * Return all structures (i.e. for for each language)
     *
     * @return Sulu\Component\Content\StructureInterface[]
     */
    public function getStructures()
    {
        $structures = array();
        foreach ($this->nodeHelper->getLanguagesForNode($this->node) as $locale) {
            $structures[] = $this->getStructure($locale);
        }

        return $structures;
    }

    /**
     * Return the PHPCR node for the structure that was deleted
     *
     * @return PHPCR\NodeInterface
     */
    public function getNode() 
    {
        return $this->node;
    }
}
