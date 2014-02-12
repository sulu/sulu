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
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\Templating\EngineInterface;

// TODO refresh whole page if rdfa not found

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

    public function __construct(
        EngineInterface $templating,
        Cache $cache,
        ContentMapperInterface $mapper,
        StructureManagerInterface $structureManager,
        ControllerResolverInterface $controllerResolver,
        $lifeTime
    )
    {
        $this->templating = $templating;
        $this->cache = $cache;
        $this->mapper = $mapper;
        $this->structureManager = $structureManager;
        $this->controllerResolver = $controllerResolver;
        $this->lifeTime = $lifeTime;
    }

    /**
     * starts a preview for given user and content
     * @param int $userId
     * @param string $contentUuid
     * @param string $workspaceKey
     * @param string $languageCode
     * @return \Sulu\Component\Content\StructureInterface
     */
    public function start($userId, $contentUuid, $workspaceKey, $languageCode)
    {
        $content = $this->mapper->load($contentUuid, $workspaceKey, $languageCode);
        $this->addStructure($userId, $contentUuid, $content);

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
     * @param string|null $template template key
     * @return \Sulu\Component\Content\StructureInterface
     * @throws PreviewNotFoundException
     */
    public function update($userId, $contentUuid, $property, $data, $template = null)
    {
        /** @var StructureInterface $content */
        $content = $this->loadStructure($userId, $contentUuid);

        if ($content != false) {
            if ($template !== null && $content->getKey() !== $template) {
                $content = $this->updateTemplate($content, $template);
                $this->addReload($userId, $contentUuid);
            }

            $this->setValue($content, $property, $data);
            $this->addStructure($userId, $contentUuid, $content);

            $changes = $this->render($userId, $contentUuid, true, $property);
            if ($changes !== false) {
                $this->addChanges($userId, $contentUuid, $property, $changes);
            }

            return $content;
        } else {
            throw new PreviewNotFoundException($userId, $contentUuid);
        }
    }

    /**
     * @param StructureInterface $content
     * @param $template
     *
     * @return StructureInterface
     */
    private function updateTemplate(StructureInterface $content, $template)
    {
        /** @var StructureInterface $newContent */
        $newContent = $this->structureManager->getStructure($template);
        /** @var PropertyInterface $property */
        foreach ($newContent->getProperties() as $property) {
            if ($content->hasProperty($property->getName())) {
                $this->setValue(
                    $newContent,
                    $property->getName(),
                    $this->getValue($content, $property->getName())
                );
            }
        }
        return $newContent;
    }

    private function setValue(StructureInterface $content, $property, $value)
    {
        // TODO check for complex content types
        $content->getProperty($property)->setValue($value);
    }

    private function getValue(StructureInterface $content, $property)
    {
        // TODO check for complex content types
        return $content->getProperty($property)->getValue();
    }

    /**
     * returns pending changes for given user and content
     * @param $userId
     * @param $contentUuid
     * @throws PreviewNotFoundException
     * @return array
     */
    public function getChanges($userId, $contentUuid)
    {
        if ($this->started($userId, $contentUuid)) {
            $result = $this->readChanges($userId, $contentUuid);
            return $result !== false ? $result : array();
        } else {
            throw new PreviewNotFoundException($userId, $contentUuid);
        }
    }

    /**
     * renders a content for given user
     * @param int $userId
     * @param string $contentUuid
     * @param bool $partial
     * @param string|null $property
     * @throws PreviewNotFoundException
     * @return string
     */
    public function render($userId, $contentUuid, $partial = false, $property = null)
    {
        /** @var StructureInterface $content */
        $content = $this->loadStructure($userId, $contentUuid);

        if ($content != false) {
            // get controller and invoke action
            $request = new Request();
            $request->attributes->set('_controller', $content->getController());
            $controller = $this->controllerResolver->getController($request);
            $response = $controller[0]->{$controller[1]}($content, true, $partial);
            $result = $response->getContent();

            // if partial render for property is called
            if ($property != null) {
                // extract special property
                $crawler = new Crawler($result);
                $nodes = $crawler->filter('*[property="' . $property . '"]');

                // if rdfa property not found return false
                if ($nodes->count() > 0) {
                    // create an array of changes
                    $result = $nodes->each(
                        function (Crawler $node) {
                            return $node->html();
                        }
                    );
                } else {
                    return false;
                }
            }

            return $result;
        } else {
            throw new PreviewNotFoundException($userId, $contentUuid);
        }
    }

    /**
     * saves data in cache
     * @param int $userId
     * @param string $contentUuid
     * @param StructureInterface $data
     * @return bool
     */
    private function addStructure($userId, $contentUuid, $data)
    {
        $cacheId = $this->getCacheKey($userId, $contentUuid);
        $structureCacheId = $this->getCacheKey($userId, $contentUuid, 'structure');

        return $this->cache->save($cacheId, $data, $this->lifeTime) &&
            $this->cache->save($structureCacheId, $data->getKey(), $this->lifeTime);
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
     * saves changes for given user and content
     * @param $userId
     * @param $contentUuid
     * @param $property
     * @param $content
     */
    private function addChanges($userId, $contentUuid, $property, $content)
    {
        $id = $this->getCacheKey($userId, $contentUuid, 'changes');
        $changes = $this->cache->fetch($id);

        if (!$changes) {
            $changes = array();
        } elseif (isset($changes['reload']) && $changes['reload'] === true) {
            return;
        }

        $changes[$property] = array('property' => $property, 'content' => $content);

        $this->cache->save($id, $changes, $this->lifeTime);
    }

    /**
     * adds a reload event to changes
     * @param $userId
     * @param $contentUuid
     */
    private function addReload($userId, $contentUuid)
    {
        $this->addChanges($userId, $contentUuid, 'reload', true);
    }

    /**
     * return changes for given user and content
     * @param $userId
     * @param $contentUuid
     * @return array
     */
    private function readChanges($userId, $contentUuid)
    {
        $id = $this->getCacheKey($userId, $contentUuid, 'changes');
        if ($this->cache->contains($id)) {
            $changes = $this->cache->fetch($id);
            // clean array if changes are read
            $this->cache->save($id, array(), $this->lifeTime);
            return $changes;
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
        $changesCacheId = $this->getCacheKey($userId, $contentUuid, 'changes');

        if ($this->cache->contains($cacheId)) {
            return $this->cache->delete($cacheId);
        }
        if ($this->cache->contains($structureCacheId)) {
            return $this->cache->delete($structureCacheId);
        }
        if ($this->cache->contains($changesCacheId)) {
            return $this->cache->delete($changesCacheId);
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
}
