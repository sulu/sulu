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
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
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

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(
        ContainerInterface $container,
        EngineInterface $templating,
        Cache $cache,
        ContentMapperInterface $mapper,
        StructureManagerInterface $structureManager,
        ControllerResolverInterface $controllerResolver,
        $lifeTime
    )
    {
        $this->container = $container;
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
     * {@inheritdoc}
     */
    public function update($userId, $contentUuid, $webspaceKey, $languageCode, $property, $data, $template = null)
    {
        /** @var StructureInterface $content */
        $content = $this->loadStructure($userId, $contentUuid);

        if ($content != false) {
            if ($template !== null && $content->getKey() !== $template) {
                $content = $this->updateTemplate($content, $template, $webspaceKey, $languageCode);
                $this->addReload($userId, $contentUuid);
            }

            if ($webspaceKey !== $content->getWebspaceKey()) {
                $content->setWebspaceKey($webspaceKey);
            }

            if ($languageCode !== $content->getLanguageCode()) {
                $content->setLanguageCode($languageCode);
            }

            $this->setValue($content, $property, $data, $webspaceKey, $languageCode);
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
     * @param $webspaceKey
     * @param $languageCode
     * @return StructureInterface
     */
    private function updateTemplate(StructureInterface $content, $template, $webspaceKey, $languageCode)
    {
        /** @var StructureInterface $newContent */
        $newContent = $this->structureManager->getStructure($template);
        $newContent->setWebspaceKey($webspaceKey);
        $newContent->setLanguageCode($languageCode);
        /** @var PropertyInterface $property */
        foreach ($newContent->getProperties() as $property) {
            $value = $content->hasProperty($property->getName()) ?
                $content->getProperty($property->getName())->getValue() : null;

            $this->setValue(
                $newContent,
                $property->getName(),
                $value,
                $webspaceKey,
                $languageCode
            );
        }
        return $newContent;
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
                $crawler = new Crawler();
                $crawler->addHtmlContent($result, 'UTF-8');
                $nodes = $crawler;
                if (false !== ($sequence = $this->getSequence($content, $property))) {
                    foreach ($sequence['sequence'] as $item) {
                        // is not integer
                        if (!ctype_digit(strval($item))) {
                            $nodes = $nodes->filter('*[property="' . $item . '"]');
                        } else {
                            $nodes = $nodes->eq($item);
                        }
                    }
                } else {
                    // FIXME it is a bit complex but there is no :not operator in crawler
                    // should be *[property="block"]:not(*[property] *)
                    $nodes = $nodes->filter('*[property="' . $property . '"]')->reduce(
                        function (Crawler $node) {
                            // get parents
                            $parents = $node->parents();
                            $count = 0;
                            // check if one parent is property exvlude it
                            $parents->each(
                                function ($node) use (&$count) {
                                    if ($node->attr('property')) {
                                        $count++;
                                    }
                                }
                            );
                            return ($count === 0);
                        }
                    );
                }

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
     * Sets the given data in the given content (including webspace and language)
     * @param StructureInterface $content
     * @param string $property
     * @param mixed $data
     * @param string $webspaceKey
     * @param string $languageCode
     */
    private function setValue(StructureInterface $content, $property, $data, $webspaceKey, $languageCode)
    {
        if (false !== ($sequence = $this->getSequence($content, $property))) {
            $tmp = $data;
            $data = $sequence['property']->getValue();;
            $value = & $data;
            $len = sizeof($sequence['index']);
            for ($i = 0; $i < $len; $i++) {
                $value = & $value[$sequence['index'][$i]];
            }
            $value = $tmp;
            $instance = $sequence['property'];
        } else {
            $instance = $content->getProperty($property);
        }
        $contentType = $this->getContentType($instance->getContentTypeName());
        $contentType->readForPreview($data, $instance, $webspaceKey, $languageCode, null);
    }

    /**
     * extracts sequence information from property name
     * implemented with memoize to avoid memory leaks
     * @param StructureInterface $content
     * @param string $property
     * @return false|array with sequence, propertypath, property instance, index sequence
     */
    private function getSequence(StructureInterface $content, $property)
    {
        // memoize start
        static $cache;
        if (!is_null($cache) && array_key_exists($property, $cache)) {
            return $cache[$property];
        }
        // memoize end

        if (false !== strpos($property, ',')) {
            $sequence = explode(',', $property);
            $propertyPath = array();
            $indexSequence = array();
            $propertyInstance = $content->getProperty($sequence[0]);
            for ($i = 1; $i < sizeof($sequence); $i++) {
                // is not integer
                if (!ctype_digit(strval($sequence[$i]))) {
                    $propertyPath[] = $sequence[$i];
                    $propertyInstance = $propertyInstance->getChild($sequence[$i]);
                } else {
                    $indexSequence[] = intval($sequence[$i]);
                }
            }
            $cache[$property] = array(
                'sequence' => $sequence,
                'propertyPath' => $propertyPath,
                'property' => $propertyInstance,
                'index' => $indexSequence
            );
        } else {
            $cache[$property] = false;
        }
        return $cache[$property];
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

    /**
     * returns a type with given name
     * @param $name
     * @return ContentTypeInterface
     */
    protected function getContentType($name)
    {
        return $this->container->get('sulu.content.type.' . $name);
    }
}
