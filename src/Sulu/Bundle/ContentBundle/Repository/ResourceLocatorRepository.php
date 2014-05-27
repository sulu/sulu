<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Repository;

use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;

/**
 * resource locator repository
 */
class ResourceLocatorRepository implements ResourceLocatorRepositoryInterface
{
    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var RlpStrategyInterface
     */
    private $strategy;

    /**
     * @var string
     */
    private $apiPath;

    /**
     * @param RlpStrategyInterface $strategy
     * @param StructureManagerInterface $structureManager
     */
    function __construct(RlpStrategyInterface $strategy, StructureManagerInterface $structureManager)
    {
        $this->strategy = $strategy;
        $this->structureManager = $structureManager;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($parts, $parentUuid, $uuid, $webspaceKey, $languageCode, $templateKey, $segmentKey = null)
    {
        /** @var StructureInterface $structure */
        $structure = $this->structureManager->getStructure($templateKey);
        $title = $this->implodeRlpParts($structure, $parts);

        if ($parentUuid !== null) {
            $parentPath = $this->strategy->loadByContentUuid($parentUuid, $webspaceKey, $languageCode, $segmentKey);
            $result = $this->strategy->generate($title, $parentPath, $webspaceKey, $languageCode, $segmentKey);
        } elseif ($uuid !== null) {
            $result = $this->strategy->generateForUuid($title, $uuid, $webspaceKey, $languageCode, $segmentKey);
        } else {
            $parentPath = '/';
            $result = $this->strategy->generate($title, $parentPath, $webspaceKey, $languageCode, $segmentKey);
        }

        return array(
            'resourceLocator' => $result,
            '_links' => array(
                'self' => $this->apiPath
            )
        );
    }

    /**
     * @param StructureInterface $structure
     * @param array $parts
     * @param string $separator default '-'
     * @return string
     */
    private function implodeRlpParts(StructureInterface $structure, array $parts, $separator = '-')
    {
        $title = '';
        // concat rlp parts in sort of priority
        foreach ($structure->getPropertiesByTagName('sulu.rlp.part') as $property) {
            $title = $parts[$property->getName()] . $separator . $title;
        }
        $title = substr($title, 0, -1);

        return $title;
    }
}
