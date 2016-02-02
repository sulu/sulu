<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Preview;

use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;

/**
 * handles preview start / stop / update / render.
 */
class Preview implements PreviewInterface
{
    /**
     * @var PreviewCacheProviderInterface
     */
    private $previewCache;

    /**
     * @var PreviewRenderer
     */
    private $renderer;

    /**
     * @var RdfaCrawler
     */
    private $crawler;

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        PreviewCacheProviderInterface $previewCache,
        PreviewRenderer $renderer,
        RdfaCrawler $crawler
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->crawler = $crawler;
        $this->previewCache = $previewCache;
        $this->renderer = $renderer;
    }

    /**
     * {@inheritdoc}
     */
    public function start($userId, $contentUuid, $webspaceKey, $locale, $data = null, $template = null)
    {
        if ($this->previewCache->contains($userId, $contentUuid, $webspaceKey, $locale)) {
            $this->previewCache->delete($userId, $contentUuid, $webspaceKey, $locale);
        }

        $result = $this->previewCache->warmUp($userId, $contentUuid, $webspaceKey, $locale);

        if ($data !== null) {
            if ($template !== null) {
                $this->previewCache->updateTemplate($template, $userId, $contentUuid, $webspaceKey, $locale);
            }

            $result = $this->updateProperties($userId, $contentUuid, $webspaceKey, $locale, $data);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function stop($userId, $contentUuid, $webspaceKey, $locale)
    {
        if ($this->previewCache->contains($userId, $contentUuid, $webspaceKey, $locale)) {
            $this->previewCache->delete($userId, $contentUuid, $webspaceKey, $locale);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function started($userId, $contentUuid, $webspaceKey, $locale)
    {
        return $this->previewCache->contains($userId, $contentUuid, $webspaceKey, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function updateProperties(
        $userId,
        $contentUuid,
        $webspaceKey,
        $locale,
        $changes
    ) {
        /** @var StructureInterface $content */
        $content = $this->previewCache->fetchStructure($userId, $contentUuid, $webspaceKey, $locale);

        if ($content === false) {
            throw new PreviewNotFoundException($userId, $contentUuid);
        }

        if (is_array($changes) && count($changes) > 0) {
            foreach ($changes as $property => $data) {
                $this->update($webspaceKey, $locale, $property, $data, $content);
            }

            $this->previewCache->saveStructure($content, $userId, $contentUuid, $webspaceKey, $locale);

            return $this->previewCache->fetchStructure($userId, $contentUuid, $webspaceKey, $locale);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function updateProperty($userId, $contentUuid, $webspaceKey, $locale, $property, $data)
    {
        /** @var StructureInterface $content */
        $content = $this->previewCache->fetchStructure($userId, $contentUuid, $webspaceKey, $locale);

        if ($content === false) {
            throw new PreviewNotFoundException($userId, $contentUuid);
        }

        $content = $this->update($webspaceKey, $locale, $property, $data, $content);
        $this->previewCache->saveStructure($content, $userId, $contentUuid, $webspaceKey, $locale);

        $this->generateChanges($userId, $webspaceKey, $locale, $property, $content);

        return $content;
    }

    /**
     * updates one property without saving structure.
     */
    private function update($webspaceKey, $locale, $property, $data, StructureInterface $content)
    {
        $this->setValue($content, $property, $data, $webspaceKey, $locale);

        return $content;
    }

    /**
     * Generate changes and save them in the cache.
     */
    private function generateChanges($userId, $webspaceKey, $locale, $property, StructureInterface $content)
    {
        $sequence = $this->crawler->getSequence($content, $property);

        if (false !== $sequence) {
            // length of property path is important to render
            $property = implode(
                ',',
                array_slice($sequence['sequence'], 0, (-1) * count($sequence['propertyPath']))
            );
        }

        try {
            $changes = $this->renderStructure($content, true, $property);
        } catch (\Twig_Error $ex) {
            throw new TwigPreviewException($ex);
        }

        if ($changes !== false) {
            $this->previewCache->appendChanges(
                [$property => $changes],
                $userId,
                $content->getUuid(),
                $webspaceKey,
                $locale
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getChanges($userId, $contentUuid, $webspaceKey, $locale)
    {
        return $this->previewCache->fetchChanges($userId, $contentUuid, $webspaceKey, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function render(
        $userId,
        $contentUuid,
        $webspaceKey,
        $locale,
        $partial = false,
        $property = null
    ) {
        if (!$this->previewCache->contains($userId, $contentUuid, $webspaceKey, $locale)) {
            throw new PreviewNotFoundException($userId, $contentUuid);
        }

        /** @var StructureInterface $content */
        $content = $this->previewCache->fetchStructure($userId, $contentUuid, $webspaceKey, $locale);

        return $this->renderStructure($content, $partial, $property);
    }

    /**
     * render structure.
     */
    private function renderStructure(
        StructureInterface $content,
        $partial = false,
        $property = null
    ) {
        $result = $this->renderer->render($content, $partial);

        // if partial render for property is called
        if ($property != null) {
            $result = $this->crawler->getPropertyValue($result, $content, $property);
        }

        return $result;
    }

    /**
     * Sets the given data in the given content (including webspace and language) and returns sequence information.
     */
    private function setValue(StructureInterface $content, $property, $data, $webspaceKey, $languageCode)
    {
        if (false !== ($sequence = $this->crawler->getSequence($content, $property))) {
            $tmp = $data;
            $data = $sequence['property']->getValue();
            $value = &$data;
            $len = count($sequence['index']);
            for ($i = 0; $i < $len; ++$i) {
                $value = &$value[$sequence['index'][$i]];
            }
            $value = $tmp;
            $instance = $sequence['property'];
        } else {
            if (!$content->hasProperty($property)) {
                return $sequence;
            }

            $instance = $content->getProperty($property);
        }

        $contentType = $this->contentTypeManager->get($instance->getContentTypeName());
        $contentType->readForPreview($data, $instance, $webspaceKey, $languageCode, null);

        return $sequence;
    }
}
