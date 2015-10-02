<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Preview;

use Doctrine\Common\Cache\Cache;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;

/**
 * provides a cache for preview with phpcr.
 */
class DoctrineCacheProvider implements PreviewCacheProviderInterface
{
    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var string
     */
    private $serializeType = 'json';

    /**
     * @var Cache
     */
    private $dataCache;

    /**
     * @var Cache
     */
    private $changesCache;

    /**
     * @var int
     */
    private $cacheLifeTime;

    /**
     * prefix for property names and node name.
     *
     * @var string
     */
    private $prefix;

    /**
     * Constructor.
     */
    public function __construct(
        ContentMapperInterface $contentMapper,
        StructureManagerInterface $structureManager,
        SerializerInterface $serializer,
        Cache $dataCache,
        Cache $changesCache,
        $prefix = 'preview',
        $cacheLifeTime = 3600
    ) {
        $this->contentMapper = $contentMapper;
        $this->structureManager = $structureManager;
        $this->serializer = $serializer;
        $this->dataCache = $dataCache;
        $this->changesCache = $changesCache;
        $this->cacheLifeTime = $cacheLifeTime;
        $this->prefix = $prefix;
    }

    /**
     * Returns cache id.
     */
    private function getId($userId, $contentUuid, $locale, $postFix = null)
    {
        return sprintf('%s:%s:%s%s', $userId, $contentUuid, $locale, ($postFix ? ':' . $postFix : ''));
    }

    /**
     * {@inheritdoc}
     */
    public function contains($userId, $contentUuid, $webspaceKey, $locale)
    {
        return $this->dataCache->contains($this->getId($userId, $contentUuid, $locale));
    }

    /**
     * {@inheritdoc}
     */
    public function delete($userId, $contentUuid, $webspaceKey, $locale)
    {
        $id = $this->getId($userId, $contentUuid, $locale);
        $classId = $this->getId($userId, $contentUuid, $locale, 'class');

        $this->dataCache->delete($id);
        $this->dataCache->delete($classId);
        $this->changesCache->delete($id);
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($userId, $contentUuid, $webspaceKey, $locale)
    {
        $this->delete($userId, $contentUuid, $webspaceKey, $locale);

        $page = $this->contentMapper->load($contentUuid, $webspaceKey, $locale);

        $this->saveStructure($page, $userId, $contentUuid, $webspaceKey, $locale);
        $this->saveChanges([], $userId, $contentUuid, $webspaceKey, $locale);

        return $this->fetchStructure($userId, $contentUuid, $webspaceKey, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchStructure($userId, $contentUuid, $webspaceKey, $locale)
    {
        $id = $this->getId($userId, $contentUuid, $locale);
        $classId = $this->getId($userId, $contentUuid, $locale, 'class');
        $context = DeserializationContext::create();
        $context->setSerializeNull(true);

        if ($this->contains($userId, $contentUuid, $webspaceKey, $locale)) {
            $class = $this->dataCache->fetch($classId);
            $data = $this->dataCache->fetch($id);

            try {
                return $this->serializer->deserialize($data, $class, $this->serializeType, $context);
            } catch (\ReflectionException $e) {
                // load all cache classes
                $this->structureManager->getStructures();

                // try again
                return $this->serializer->deserialize($data, $class, $this->serializeType, $context);
            }
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function saveStructure(StructureInterface $content, $userId, $contentUuid, $webspaceKey, $locale)
    {
        $context = SerializationContext::create();
        $context->setSerializeNull(true);

        $data = $this->serializer->serialize($content, $this->serializeType, $context);

        $id = $this->getId($userId, $contentUuid, $locale);
        $classId = $this->getId($userId, $contentUuid, $locale, 'class');
        $this->dataCache->save($id, $data, $this->cacheLifeTime);
        $this->dataCache->save($classId, get_class($content), $this->cacheLifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchChanges($userId, $contentUuid, $webspaceKey, $locale, $remove = true)
    {
        $id = $this->getId($userId, $contentUuid, $locale);
        $changes = $this->changesCache->fetch($id);

        if ($remove) {
            $this->saveChanges([], $userId, $contentUuid, $webspaceKey, $locale);
        }

        return $changes ?: [];
    }

    /**
     * {@inheritdoc}
     */
    public function saveChanges($changes, $userId, $contentUuid, $webspaceKey, $locale)
    {
        $id = $this->getId($userId, $contentUuid, $locale);
        $this->changesCache->save($id, $changes, $this->cacheLifeTime);

        return $this->changesCache->fetch($id);
    }

    /**
     * {@inheritdoc}
     */
    public function updateTemplate($template, $userId, $contentUuid, $webspaceKey, $locale)
    {
        /** @var PageBridge $structure */
        $structure = $this->fetchStructure($userId, $contentUuid, $webspaceKey, $locale);
        /** @var PageBridge $newStructure */
        $newStructure = $this->structureManager->getStructure($template);

        $newStructure->copyFrom($structure);

        $this->saveStructure($newStructure, $userId, $contentUuid, $webspaceKey, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function appendChanges($newChanges, $userId, $contentUuid, $webspaceKey, $locale)
    {
        $oldChanges = $this->fetchChanges($userId, $contentUuid, $webspaceKey, $locale, false);
        $newChanges = array_merge($oldChanges, $newChanges);

        return $this->saveChanges($newChanges, $userId, $contentUuid, $webspaceKey, $locale);
    }
}
