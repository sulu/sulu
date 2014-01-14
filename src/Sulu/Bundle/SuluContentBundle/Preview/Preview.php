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
use Sulu\Component\Content\StructureManagerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;

class Preview implements PreviewInterface
{
    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var ContentMapperInterface
     */
    private $mapper;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var integer
     */
    private $lifeTime;

    /**
     * @var string
     */
    private $templateNamespace;

    public function __construct(
        EngineInterface $templating,
        Cache $cache,
        ContentMapperInterface $mapper,
        StructureManagerInterface $structureManager,
        $lifeTime,
        $templateNamespace = 'ClientWebsiteBundle:Website:'
    )
    {
        $this->templating = $templating;
        $this->cache = $cache;
        $this->mapper = $mapper;
        $this->structureManager = $structureManager;
        $this->lifeTime = $lifeTime;
        $this->templateNamespace = $templateNamespace;
    }

    /**
     * starts a preview for given user and content
     * @param int $userId
     * @param string $contentUuid
     * @param string $workspaceKey
     * @param string $languageCode
     * @return StructureInterface
     */
    public function start($userId, $contentUuid, $workspaceKey, $languageCode)
    {
        $content = $this->mapper->load($contentUuid, $workspaceKey, $languageCode);
        $this->saveStructure($userId, $contentUuid, $content);

        return $content;
    }

    /**
     * stops a preview
     * @param int $userId
     * @param string $contentUuid
     */
    public function stop($userId, $contentUuid)
    {
        $this->deleteStructure($userId, $contentUuid);
    }

    /**
     * returns if a preview started for user and content
     * @param $userId
     * @param $contentUuid
     * @return bool
     */
    public function started($userId, $contentUuid)
    {
        return $this->cache->contains($this->getCacheKey($userId, $contentUuid));
    }

    /**
     * saves changes for given user and content
     * @param int $userId
     * @param string $contentUuid
     * @param string $property propertyName which was changed
     * @param mixed $data new data
     * @return string
     */
    public function update($userId, $contentUuid, $property, $data)
    {
        /** @var StructureInterface $content */
        $content = $this->loadStructure($userId, $contentUuid);

        // TODO check for complex content types
        $content->getProperty($property)->setValue($data);
        $this->saveStructure($userId, $contentUuid, $content);

        return $this->render($userId, $contentUuid, $property);
    }

    /**
     * renders a content for given user
     * @param int $userId
     * @param string $contentUuid
     * @param string|null $property
     * @return string
     */
    public function render($userId, $contentUuid, $property = null)
    {
        /** @var StructureInterface $content */
        $content = $this->loadStructure($userId, $contentUuid);

        $result = $this->renderView(
            $this->templateNamespace . $content->getView(),
            array(
                'content' => $content
            )
        );

        if ($property != null) {
            $crawler = new Crawler($result);
            $nodes = $crawler->filter('*[property="' . $property . '"]');
            $result = $nodes->first()->html();
        }

        return $result;
    }

    /**
     * saves data in cache
     * @param int $userId
     * @param string $contentUuid
     * @param StructureInterface $data
     * @return bool
     */
    private function saveStructure($userId, $contentUuid, $data)
    {
        $cacheId = $this->getCacheKey($userId, $contentUuid);
        $structureCacheId = $this->getCacheKey($userId, $contentUuid, 'structure');

        return $this->cache->save($cacheId, $data, $this->lifeTime) && $this->cache->save(
            $structureCacheId,
            $data->getKey()
        );
    }

    /**
     * returns cache value
     * @param int $userId
     * @param string $contentUuid
     * @return bool|mixed
     */
    private function loadStructure($userId, $contentUuid)
    {
        // preload structure class
        $structureCacheId = $this->getCacheKey($userId, $contentUuid, 'structure');
        $structureKey = $this->cache->fetch($structureCacheId);
        $this->structureManager->getStructure($structureKey);

        $id = $this->getCacheKey($userId, $contentUuid);

        if ($this->cache->contains($id)) {
            return $this->cache->fetch($id);
        }
        return false;
    }

    /**
     * delete cache entry
     * @param int $userId
     * @param string $contentUuid
     * @return bool
     */
    private function deleteStructure($userId, $contentUuid)
    {
        $cacheId = $this->getCacheKey($userId, $contentUuid);
        $structureCacheId = $this->getCacheKey($userId, $contentUuid, 'structure');

        if ($this->cache->contains($cacheId)) {
            return $this->cache->delete($cacheId);
        }
        if ($this->cache->contains($structureCacheId)) {
            return $this->cache->delete($structureCacheId);
        }

        return true;
    }

    /**
     * returns cache key
     * @param int $userId
     * @param string $contentUuid
     * @param bool $subKey
     * @return string
     */
    private function getCacheKey($userId, $contentUuid, $subKey = false)
    {
        return $userId . ':' . $contentUuid . ($subKey != false ? ':' . $subKey : '');
    }

    /**
     * render a content
     * @param string $view
     * @param array $parameters
     * @return string
     */
    private function renderView($view, array $parameters = array())
    {
        return $this->templating->render($view, $parameters);
    }
}
