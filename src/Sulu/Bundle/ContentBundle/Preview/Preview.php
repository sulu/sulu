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
     * @var PreviewCacheProviderInterface
     */
    private $cache;

    /**
     * @var Cache
     */
    private $changesCache;

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
        PreviewCacheProviderInterface $cache,
        Cache $changesCache,
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
        $this->changesCache = $changesCache;
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
        $this->addStructure($contentUuid, $content, $webspaceKey, $languageCode);

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function stop($userId, $contentUuid, $webspaceKey, $locale)
    {
        $this->deleteStructure($userId, $contentUuid, $webspaceKey, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function started($userId, $contentUuid, $webspaceKey, $locale)
    {
        return $this->cache->contains($contentUuid, $webspaceKey, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function update($userId, $contentUuid, $webspaceKey, $templateKey, $languageCode, $property, $data)
    {
        /** @var StructureInterface $content */
        $content = $this->loadStructure($contentUuid, $webspaceKey, $languageCode);

        if ($content != false) {
            $this->setValue($content, $property, $data, $webspaceKey, $languageCode);
            $this->addStructure($contentUuid, $content, $webspaceKey, $languageCode);

            if (false !== ($sequence = $this->getSequence($content, $property))) {
                // length of property path is important to render
                $property = implode(
                    ',',
                    array_slice($sequence['sequence'], 0, (-1) * sizeof($sequence['propertyPath']))
                );
            }
            $changes = $this->render($userId, $contentUuid, $templateKey, $languageCode, $webspaceKey, true, $property);
            if ($changes !== false) {
                $this->addChanges($userId, $property, $changes);
            }

            return $content;
        } else {
            throw new PreviewNotFoundException($userId, $contentUuid);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateTemplate($userId, $contentUuid, $templateKey, $webspaceKey, $languageCode)
    {
        /** @var StructureInterface $content */
        $content = $this->loadStructure($contentUuid, $webspaceKey, $languageCode);

        if ($content->getKey() !== $templateKey) {
            /** @var StructureInterface $newContent */
            $newContent = $this->structureManager->getStructure($templateKey);
            $newContent->setWebspaceKey($webspaceKey);
            $newContent->setLanguageCode($languageCode);

            $this->copyProperties($newContent, $content, $webspaceKey, $languageCode);
            $newContent->setExt($content->getExt());

            $this->addReload($userId);

            $this->addStructure($contentUuid, $newContent, $webspaceKey, $languageCode);
        }
    }

    /**
     * adds a reload event to changes
     */
    private function addReload($userId)
    {
        $this->addChanges($userId, 'reload', true);
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
    public function getChanges($userId, $contentUuid, $webspaceKey, $languageCode)
    {
        if ($this->started($userId, $contentUuid, $webspaceKey, $languageCode)) {
            $result = $this->readChanges($userId);

            return $result !== false ? $result : array();
        } else {
            throw new PreviewNotFoundException($userId, $contentUuid);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function render(
        $userId,
        $contentUuid,
        $templateKey,
        $languageCode,
        $webspaceKey,
        $partial = false,
        $property = null
    )
    {
        /** @var StructureInterface $content */
        $content = $this->loadStructure($contentUuid, $webspaceKey, $languageCode);

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
     */
    private function addStructure($contentUuid, $data, $webspaceKey, $languageCode)
    {
        return $this->cache->save($contentUuid, $data, $webspaceKey, $languageCode);
    }

    /**
     * returns cache value
     */
    private function loadStructure($contentUuid, $webspaceKey, $languageCode)
    {
        return $this->cache->fetch($contentUuid, $webspaceKey, $languageCode);
    }

    /**
     * saves changes for given user and content
     */
    private function addChanges($userId, $property, $content)
    {
        $id = $this->getCacheKey($userId, 'changes');
        $changes = $this->changesCache->fetch($id);

        if (!$changes) {
            $changes = array();
        } elseif (isset($changes['reload']) && $changes['reload'] === true) {
            return;
        }

        $changes[$property] = array('property' => $property, 'content' => $content);

        $this->changesCache->save($id, $changes, $this->lifeTime);
    }

    /**
     * return changes for given user and content
     */
    private function readChanges($userId)
    {
        $id = $this->getCacheKey($userId,'changes');
        if ($this->changesCache->contains($id)) {
            $changes = $this->changesCache->fetch($id);
            // clean array if changes are read
            $this->changesCache->save($id, array(), $this->lifeTime);

            return $changes;
        }

        return false;
    }

    /**
     * delete cache entries
     */
    private function deleteStructure($userId, $contentUuid, $webspaceKey, $locale)
    {
        $changesCacheId = $this->getCacheKey($userId, $contentUuid, 'changes');

        if ($this->cache->contains($contentUuid, $webspaceKey, $locale)) {
            return $this->cache->delete($contentUuid, $webspaceKey);
        }
        if ($this->changesCache->contains($changesCacheId)) {
            return $this->changesCache->delete($changesCacheId);
        }

        return true;
    }

    /**
     * returns cache key
     */
    private function getCacheKey($userId, $subKey = false)
    {
        return sprintf(
            'U%s-%s',
            $userId,
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
