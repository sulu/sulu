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
use Liip\ThemeBundle\ActiveTheme;
use Sulu\Component\Content\Block\BlockPropertyInterface;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\Section\SectionPropertyInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\Templating\EngineInterface;

// TODO refresh whole page if rdfa not found

/**
 * handles preview
 */
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
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var ActiveTheme
     */
    private $activeTheme;

    public function __construct(
        EngineInterface $templating,
        Cache $cache,
        ContentMapperInterface $mapper,
        StructureManagerInterface $structureManager,
        ContentTypeManagerInterface $contentTypeManager,
        ControllerResolverInterface $controllerResolver,
        WebspaceManagerInterface $webspaceManager,
        ActiveTheme $activeTheme,
        $lifeTime
    ) {
        $this->templating = $templating;
        $this->cache = $cache;
        $this->mapper = $mapper;
        $this->structureManager = $structureManager;
        $this->contentTypeManager = $contentTypeManager;
        $this->controllerResolver = $controllerResolver;
        $this->webspaceManager = $webspaceManager;
        $this->activeTheme = $activeTheme;
        $this->lifeTime = $lifeTime;
    }

    /**
     * {@inheritdoc}
     */
    public function start($userId, $contentUuid, $webspaceKey, $templateKey, $languageCode)
    {
        $content = $this->mapper->load($contentUuid, $webspaceKey, $languageCode);
        if ($content->getKey() !== $templateKey) {
            $content = $this->updateTemplate($content, $templateKey, $webspaceKey, $languageCode);
        }
        $this->addStructure($userId, $contentUuid, $content, $templateKey, $languageCode);

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function stop($userId, $contentUuid, $templateKey, $languageCode)
    {
        $this->deleteStructure($userId, $contentUuid, $templateKey, $languageCode);
    }

    /**
     * {@inheritdoc}
     */
    public function started($userId, $contentUuid, $templateKey, $languageCode)
    {
        return $this->cache->contains($this->getCacheKey($userId, $contentUuid, $templateKey, $languageCode));
    }

    /**
     * {@inheritdoc}
     */
    public function update($userId, $contentUuid, $webspaceKey, $templateKey, $languageCode, $property, $data)
    {
        /** @var StructureInterface $content */
        $content = $this->loadStructure($userId, $contentUuid, $templateKey, $languageCode);

        if ($content != false) {
            $this->setValue($content, $property, $data, $webspaceKey, $languageCode);
            $this->addStructure($userId, $contentUuid, $content, $templateKey, $languageCode);

            if (false !== ($sequence = $this->getSequence($content, $property))) {
                // length of property path is important to render
                $property = implode(
                    ',',
                    array_slice($sequence['sequence'], 0, (-1) * sizeof($sequence['propertyPath']))
                );
            }
            $changes = $this->render($userId, $contentUuid, $templateKey, $languageCode, true, $property);
            if ($changes !== false) {
                $this->addChanges($userId, $contentUuid, $property, $changes, $templateKey, $languageCode);
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

        $this->copyProperties($newContent, $content, $webspaceKey, $languageCode);

        return $newContent;
    }

    /**
     * copies properties from one to another node
     * @param StructureInterface|SectionPropertyInterface $newContent
     * @param StructureInterface|SectionPropertyInterface $content
     * @param string $webspaceKey
     * @param string $languageCode
     */
    private function copyProperties(
        $newContent,
        $content,
        $webspaceKey,
        $languageCode
    ) {
        /** @var PropertyInterface $property */
        foreach ($newContent->getProperties(true) as $property) {
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
    }

    /**
     * {@inheritdoc}
     */
    public function getChanges($userId, $contentUuid, $templateKey, $languageCode)
    {
        if ($this->started($userId, $contentUuid, $templateKey, $languageCode)) {
            $result = $this->readChanges($userId, $contentUuid, $templateKey, $languageCode);

            return $result !== false ? $result : array();
        } else {
            throw new PreviewNotFoundException($userId, $contentUuid);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function render($userId, $contentUuid, $templateKey, $languageCode, $partial = false, $property = null)
    {
        /** @var StructureInterface $content */
        $content = $this->loadStructure($userId, $contentUuid, $templateKey, $languageCode);

        if ($content != false) {
            // set active theme
            $webspace = $this->webspaceManager->findWebspaceByKey($content->getWebspaceKey());
            $this->activeTheme->setName($webspace->getTheme()->getKey());

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
                            $before = $item;
                            $nodes = $nodes->filter('*[property="' . $item . '"]');
                        } else {
                            $nodes = $nodes->filter('*[rel="' . $before . '"]')->eq($item);
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
            $data = $sequence['property']->getValue();
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
        // FIXME websocket loses couple between structure and instance
//        static $cache;
//        if (!is_null($cache) && array_key_exists($property, $cache)) {
//            return $cache[$property];
//        }
        // memoize end

        $cache = array();
        if (false !== strpos($property, ',')) {
            $sequence = explode(',', $property);
            $propertyPath = array();
            $indexSequence = array();
            $propertyInstance = $content->getProperty($sequence[0]);
            for ($i = 1; $i < sizeof($sequence); $i++) {
                // is not integer
                if (!ctype_digit(strval($sequence[$i]))) {
                    $propertyPath[] = $sequence[$i];
                    if ($propertyInstance instanceof BlockPropertyInterface) {
                        $lastIndex = $indexSequence[sizeof($indexSequence) - 1];

                        unset($indexSequence[sizeof($indexSequence) - 1]);
                        $indexSequence = array_values($indexSequence);

                        $propertyInstance = $propertyInstance->getProperties($lastIndex)[$sequence[$i]];
                    }
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
     * @param string $templateKey
     * @param string $languageCode
     * @return bool
     */
    private function addStructure($userId, $contentUuid, $data, $templateKey, $languageCode)
    {
        $cacheId = $this->getCacheKey($userId, $contentUuid, $templateKey, $languageCode);
        $structureCacheId = $this->getCacheKey($userId, $contentUuid, $templateKey, $languageCode, 'structure');

        return $this->cache->save($cacheId, $data, $this->lifeTime) &&
        $this->cache->save($structureCacheId, $data->getKey(), $this->lifeTime);
    }

    /**
     * returns cache value
     * @param int $userId
     * @param string $contentUuid
     * @param string $templateKey
     * @param string $languageCode
     * @return bool|mixed
     */
    private function loadStructure($userId, $contentUuid, $templateKey, $languageCode)
    {
        // preload structure class
        $structureCacheId = $this->getCacheKey($userId, $contentUuid, $templateKey, $languageCode, 'structure');
        $structureKey = $this->cache->fetch($structureCacheId);
        $this->structureManager->getStructure($structureKey);

        $id = $this->getCacheKey($userId, $contentUuid, $templateKey, $languageCode);

        if ($this->cache->contains($id)) {
            return $this->cache->fetch($id);
        }

        return false;
    }

    /**
     * saves changes for given user and content
     * @param string $userId
     * @param string $contentUuid
     * @param string $property
     * @param string $content
     * @param string $templateKey
     * @param string $languageCode
     */
    private function addChanges($userId, $contentUuid, $property, $content, $templateKey, $languageCode)
    {
        $id = $this->getCacheKey($userId, $contentUuid, $templateKey, $languageCode, 'changes');
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
     * @param $templateKey
     * @param $languageCode
     */
    private function addReload($userId, $contentUuid, $templateKey, $languageCode)
    {
        $this->addChanges($userId, $contentUuid, $templateKey, $languageCode, 'reload', true);
    }

    /**
     * return changes for given user and content
     * @param $userId
     * @param $contentUuid
     * @param $templateKey
     * @param $languageCode
     * @return array
     */
    private function readChanges($userId, $contentUuid, $templateKey, $languageCode)
    {
        $id = $this->getCacheKey($userId, $contentUuid, $templateKey, $languageCode, 'changes');
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
     * @param $templateKey
     * @param $languageCode
     * @return bool
     */
    private function deleteStructure($userId, $contentUuid, $templateKey, $languageCode)
    {
        $cacheId = $this->getCacheKey($userId, $contentUuid, $templateKey, $languageCode);
        $structureCacheId = $this->getCacheKey($userId, $contentUuid, $templateKey, $languageCode, 'structure');
        $changesCacheId = $this->getCacheKey($userId, $contentUuid, $templateKey, $languageCode, 'changes');

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
     * @param string $templateKey
     * @param string $languageCode
     * @param bool $subKey
     * @return string
     */
    private function getCacheKey($userId, $contentUuid, $templateKey, $languageCode, $subKey = false)
    {
        return sprintf(
            'U%s:C%s:T%s:L%s%s',
            $userId,
            $contentUuid,
            $templateKey,
            $languageCode,
            ($subKey != false ? ':' . $subKey : '')
        );
    }

    /**
     * returns a type with given name
     * @param $name
     * @return ContentTypeInterface
     */
    protected function getContentType($name)
    {
        return $this->contentTypeManager->get($name);
    }
}
