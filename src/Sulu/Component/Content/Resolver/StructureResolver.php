<?php

namespace Sulu\Component\Content\Resolver;

use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\StructureInterface;

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
     * @param ContentTypeManagerInterface $contentTypeManager
     */
    public function __construct(ContentTypeManagerInterface $contentTypeManager)
    {
        $this->contentTypeManager = $contentTypeManager;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(StructureInterface $structure)
    {
        $data = array(
            'webspaceKey' => $structure->getWebspaceKey(),
            'locale' => $structure->getLanguageCode(),
            'view' => array(),
            'content' => array(),
            'extension' => $structure->getExt(),
            'uuid' => $structure->getUuid(),
            'creator' => $structure->getCreator(),
            'changer' => $structure->getChanger(),
            'created' => $structure->getCreated(),
            'changed' => $structure->getChanged(),
        );

        foreach ($structure->getProperties(true) as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());
            $data['view'][$property->getName()] = $contentType->getViewData($property);
            $data['content'][$property->getName()] = $contentType->getContentData($property);
        }

        return $data;
    }
}
