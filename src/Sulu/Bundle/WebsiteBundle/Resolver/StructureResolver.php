<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\LocalizedAuthorBehavior;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;

/**
 * Class that "resolves" the view data for a given structure.
 */
class StructureResolver implements StructureResolverInterface
{
    /**
     * @var ContentTypeManagerInterface
     */
    protected $contentTypeManager;

    /**
     * @var ExtensionManagerInterface
     */
    protected $extensionManager;

    /**
     * @param ContentTypeManagerInterface $contentTypeManager
     * @param ExtensionManagerInterface $structureManager
     */
    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        ExtensionManagerInterface $structureManager
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->extensionManager = $structureManager;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(StructureInterface $structure, bool $loadExcerpt = false)
    {
        $data = [
            'view' => [],
            'content' => [],
            'uuid' => $structure->getUuid(),
            'creator' => $structure->getCreator(),
            'changer' => $structure->getChanger(),
            'created' => $structure->getCreated(),
            'changed' => $structure->getChanged(),
            'template' => $structure->getKey(),
            'path' => $structure->getPath(),
        ];

        $document = $structure->getDocument();
        if ($document instanceof ExtensionBehavior && $loadExcerpt) {
            $data['extension'] = $structure->getExt() ? $structure->getExt()->toArray() : [];
            foreach ($data['extension'] as $name => $value) {
                $extension = $this->extensionManager->getExtension($structure->getKey(), $name);
                $data['extension'][$name] = $extension->getContentData($value);
            }
        }

        if ($structure instanceof PageBridge) {
            $data['urls'] = $structure->getUrls();
            $data['published'] = $structure->getPublished();
            $data['shadowBaseLocale'] = $structure->getShadowBaseLanguage();
            $data['webspaceKey'] = $structure->getWebspaceKey();

            if ($document instanceof LocalizedAuthorBehavior) {
                $data['authored'] = $document->getAuthored();
                $data['author'] = $document->getAuthor();
            }
        }

        // pre-resolve content-types
        foreach ($structure->getProperties(true) as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());

            if ($contentType instanceof PreResolvableContentTypeInterface) {
                $contentType->preResolve($property);
            }
        }

        foreach ($structure->getProperties(true) as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());
            $data['view'][$property->getName()] = $contentType->getViewData($property);
            $data['content'][$property->getName()] = $contentType->getContentData($property);
        }

        return $data;
    }
}
