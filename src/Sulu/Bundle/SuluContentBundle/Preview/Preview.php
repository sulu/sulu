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
use DOMElement;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\StructureInterface;
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
     * @var integer
     */
    private $lifeTime;

    private $templateNamespace;

    public function __construct(
        EngineInterface $templating,
        Cache $cache,
        ContentMapperInterface $mapper,
        $lifeTime,
        $templateNamespace = 'ClientWebsiteBundle:Website:'
    )
    {
        $this->templating = $templating;
        $this->cache = $cache;
        $this->mapper = $mapper;
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
    public function startPreview($userId, $contentUuid, $workspaceKey, $languageCode)
    {
        $content = $this->mapper->load($contentUuid, $workspaceKey, $languageCode);
        $this->saveCache($userId, $contentUuid, $content);

        return $content;
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
        $content = $this->loadCache($userId, $contentUuid);

        // TODO check for complex content types
        $content->getProperty($property)->setValue($data);
        $this->saveCache($userId, $contentUuid, $content);

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
        $content = $this->loadCache($userId, $contentUuid);

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

    private function saveCache($userId, $contentUuid, $data)
    {
        $id = $this->getCacheKey($userId, $contentUuid);

        return $this->cache->save($id, $data, $this->lifeTime);
    }

    private function loadCache($userId, $contentUuid)
    {
        $id = $this->getCacheKey($userId, $contentUuid);

        if ($this->cache->contains($id)) {
            return $this->cache->fetch($id);
        }
        return false;
    }

    private function getCacheKey($userId, $contentUuid)
    {
        return $userId . ':' . $contentUuid;
    }

    private function renderView($view, array $parameters = array())
    {
        return $this->templating->render($view, $parameters);
    }
}
