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
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Types\ResourceLocatorInterface;
use Sulu\Component\Content\Types\Rlp\ResourceLocatorInformation;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;

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
     * @var RlpStrategyInterface
     */
    private $strategy;

    /**
     * @var ResourceLocatorInterface
     */
    private $resourceLocator;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var string[]
     */
    private $apiBasePath = [
        '/admin/api/node/resourcelocator',
        '/admin/api/nodes/resourcelocators',
        '/admin/api/nodes/{uuid}/resourcelocators',
    ];

    /**
     * Constructor.
     */
    public function __construct(
        RlpStrategyInterface $strategy,
        StructureManagerInterface $structureManager,
        ResourceLocatorInterface $resourceLocator,
        ContentMapperInterface $contentMapper
    ) {
        $this->strategy = $strategy;
        $this->structureManager = $structureManager;
        $this->resourceLocator = $resourceLocator;
        $this->contentMapper = $contentMapper;
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

        return [
            'resourceLocator' => $result,
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
        $urls = $this->resourceLocator->loadHistoryByUuid($uuid, $webspaceKey, $languageCode);

        $result = [];
        /** @var ResourceLocatorInformation $url */
        foreach ($urls as $url) {
            $defaultParameter = '&language=' . $languageCode . '&webspace=' . $webspaceKey;
            $deleteParameter = '?path=' . $url->getResourceLocator() . $defaultParameter;
            $restoreParameter = '/restore?path=' . $url->getResourceLocator() . $defaultParameter;

            $result[] = [
                'id' => $url->getId(),
                'resourceLocator' => $url->getResourceLocator(),
                'created' => $url->getCreated(),
                '_links' => [
                    'delete' => $this->getBasePath(null, 0) . $deleteParameter,
                    'restore' => $this->getBasePath(null, 0) . $restoreParameter,
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
        $this->resourceLocator->deleteByPath($path, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * {@inheritdoc}
     */
    public function restore($path, $userId, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $this->contentMapper->restoreHistoryPath($path, $userId, $webspaceKey, $languageCode, $segmentKey);

        return ['resourceLocator' => $path, '_links' => []];
    }

    /**
     * returns base path fo given uuid.
     *
     * @param null|string $uuid
     * @param int         $default
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
     * @param array              $parts
     * @param string             $separator default '-'
     *
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
