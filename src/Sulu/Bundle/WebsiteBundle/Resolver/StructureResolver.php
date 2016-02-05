<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;

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
     * @param ExtensionManagerInterface   $structureManager
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
    public function resolve(StructureInterface $structure)
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

        if ($structure instanceof PageBridge) {
            $data['extension'] = $structure->getExt()->toArray();
            $data['urls'] = $structure->getUrls();
            $data['published'] = $structure->getPublished();
            $data['shadowBaseLocale'] = $structure->getShadowBaseLanguage();

            foreach ($data['extension'] as $name => $value) {
                $extension = $this->extensionManager->getExtension($structure->getKey(), $name);
                $data['extension'][$name] = $extension->getContentData($value);
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
