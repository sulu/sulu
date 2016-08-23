<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Repository;

use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Types\ResourceLocator\ResourceLocatorInformation;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;

/**
 * resource locator repository.
 */
class ResourceLocatorRepository implements ResourceLocatorRepositoryInterface
{
    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var ResourceLocatorStrategyPoolInterface
     */
    private $resourceLocatorStrategyPool;

    /**
     * @var string[]
     */
    private $apiBasePath = [
        '/admin/api/node/resourcelocator',
        '/admin/api/nodes/resourcelocators',
        '/admin/api/nodes/{uuid}/resourcelocators',
    ];

    /**
     * @param ResourceLocatorStrategyPoolInterface $resourceLocatorStrategyPool
     * @param StructureManagerInterface $structureManager
     */
    public function __construct(
        ResourceLocatorStrategyPoolInterface $resourceLocatorStrategyPool,
        StructureManagerInterface $structureManager
    ) {
        $this->resourceLocatorStrategyPool = $resourceLocatorStrategyPool;
        $this->structureManager = $structureManager;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($parts, $parentUuid, $webspaceKey, $languageCode, $templateKey, $segmentKey = null)
    {
        /** @var StructureInterface $structure */
        $structure = $this->structureManager->getStructure($templateKey);
        $title = $this->implodeRlpParts($structure, $parts);

        $resourceLocatorStrategy = $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey($webspaceKey);
        $resourceLocator = $resourceLocatorStrategy->generate($title, $parentUuid, $webspaceKey, $languageCode, $segmentKey);

        return [
            'resourceLocator' => $resourceLocator,
            '_links' => [
                'self' => $this->getBasePath() . '/generates',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getHistory($uuid, $webspaceKey, $languageCode)
    {
        $resourceLocatorStrategy = $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey($webspaceKey);
        $urls = $resourceLocatorStrategy->loadHistoryByContentUuid($uuid, $webspaceKey, $languageCode);

        $result = [];
        /** @var ResourceLocatorInformation $url */
        foreach ($urls as $url) {
            $defaultParameter = '&language=' . $languageCode . '&webspace=' . $webspaceKey;
            $deleteParameter = '?path=' . $url->getResourceLocator() . $defaultParameter;

            $result[] = [
                'id' => $url->getId(),
                'resourceLocator' => $url->getResourceLocator(),
                'created' => $url->getCreated(),
                '_links' => [
                    'delete' => $this->getBasePath(null, 0) . $deleteParameter,
                ],
            ];
        }

        return [
            '_embedded' => [
                'resourcelocators' => $result,
            ],
            '_links' => [
                'self' => $this->getBasePath($uuid) . '/history?language=' . $languageCode . '&webspace=' . $webspaceKey,
            ],
            'total' => count($result),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $resourceLocatorStrategy = $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey($webspaceKey);
        $resourceLocatorStrategy->deleteByPath($path, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * returns base path fo given uuid.
     *
     * @param null|string $uuid
     * @param int $default
     *
     * @return string
     */
    private function getBasePath($uuid = null, $default = 1)
    {
        if ($uuid !== null) {
            return str_replace('{uuid}', $uuid, $this->apiBasePath[2]);
        } else {
            return $this->apiBasePath[$default];
        }
    }

    /**
     * @param StructureInterface $structure
     * @param array $parts
     * @param string $separator default '-'
     *
     * @return string
     */
    private function implodeRlpParts(StructureInterface $structure, array $parts, $separator = '-')
    {
        $title = '';
        // concat rlp parts in sort of priority
        foreach ($structure->getPropertiesByTagName('sulu.rlp.part') as $property) {
            if (array_key_exists($property->getName(), $parts)) {
                $title = $parts[$property->getName()] . $separator . $title;
            }
        }
        $title = substr($title, 0, -1);

        return $title;
    }
}
