<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Preview;

use Doctrine\Common\Cache\Cache;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureSerializer\StructureSerializerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

/**
 * provides a cache for preview with phpcr
 */
class DoctrineCacheProvider implements PreviewCacheProviderInterface
{
    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var StructureSerializerInterface
     */
    private $serializer;

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
    private $lifeTime;

    /**
     * prefix for property names and node name
     * @var string
     */
    private $prefix;

    /**
     * Constructor
     */
    public function __construct(
        ContentMapperInterface $contentMapper,
        StructureSerializerInterface $structureSerializer,
        Cache $dataCache,
        Cache $changesCache,
        $prefix = 'preview',
        $cacheLifeTime = 3600
    )
    {
        $this->contentMapper = $contentMapper;
        $this->serializer = $structureSerializer;
        $this->dataCache = $dataCache;
        $this->changesCache = $changesCache;
        $this->lifeTime = $cacheLifeTime;
        $this->prefix = $prefix;
    }

    /**
     * Returns cache id
     */
    private function getId($userId, $contentUuid, $locale)
    {
        return sprintf('%s:%s:%s', $userId, $contentUuid, $locale);
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
        $this->dataCache->delete($id);
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
        $this->saveChanges(array(), $userId, $contentUuid, $webspaceKey, $locale);

        return $this->fetchStructure($userId, $contentUuid, $webspaceKey, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchStructure($userId, $contentUuid, $webspaceKey, $locale)
    {
        $id = $this->getId($userId, $contentUuid, $locale);

        if ($this->contains($userId, $contentUuid, $webspaceKey, $locale)) {
            return $this->serializer->deserialize(
                unserialize($this->dataCache->fetch($id))
            );
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function saveStructure(StructureInterface $content, $userId, $contentUuid, $webspaceKey, $locale)
    {
        $data = $this->serializer->serialize($content);

        $id = $this->getId($userId, $contentUuid, $locale);
        $this->dataCache->save($id, serialize($data), $this->lifeTime);

        return $this->fetchStructure($userId, $contentUuid, $webspaceKey, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchChanges($userId, $contentUuid, $webspaceKey, $locale, $remove = true)
    {
        $id = $this->getId($userId, $contentUuid, $locale);
        $changes = unserialize($this->changesCache->fetch($id));

        if ($remove) {
            $this->saveChanges(array(), $userId, $contentUuid, $webspaceKey, $locale);
        }

        return $changes;
    }

    /**
     * {@inheritdoc}
     */
    public function saveChanges($changes, $userId, $contentUuid, $webspaceKey, $locale)
    {
        $id = $this->getId($userId, $contentUuid, $locale);
        $this->changesCache->save($id, serialize($changes), $this->lifeTime);

        return unserialize($this->changesCache->fetch($id));
    }

    /**
     * {@inheritdoc}
     */
    public function updateTemplate($template, $userId, $contentUuid, $webspaceKey, $locale)
    {
        $structure = $this->fetchStructure($userId, $contentUuid, $webspaceKey, $locale);
        $data = $this->serializer->serialize($structure);
        $data['template'] = $template;
        $structure = $this->serializer->deserialize($data);

        $this->saveStructure($structure, $userId, $contentUuid, $webspaceKey, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function appendChanges($changes, $userId, $contentUuid, $webspaceKey, $locale)
    {
        $changes = array_merge($this->fetchChanges($userId, $contentUuid, $webspaceKey, $locale), $changes);

        return $this->saveChanges($changes, $userId, $contentUuid, $webspaceKey, $locale);
    }
}
