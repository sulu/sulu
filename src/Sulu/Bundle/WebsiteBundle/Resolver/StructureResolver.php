<?php

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Structure\Page;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;

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
     * @var StructureManagerInterface
     */
    protected $structureManager;

    /**
     * @param ContentTypeManagerInterface $contentTypeManager
     * @param StructureManagerInterface $structureManager
     */
    public function __construct(ContentTypeManagerInterface $contentTypeManager, StructureManagerInterface $structureManager)
    {
        $this->contentTypeManager = $contentTypeManager;
        $this->structureManager = $structureManager;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(StructureInterface $structure)
    {
        $data = array(
            'view' => array(),
            'content' => array(),
            'uuid' => $structure->getUuid(),
            'creator' => $structure->getCreator(),
            'changer' => $structure->getChanger(),
            'created' => $structure->getCreated(),
            'changed' => $structure->getChanged(),
        );

        if($structure instanceof Page){
            $data['extension'] = $structure->getExt();

            foreach ($data['extension'] as $name => $value) {
                $extension = $this->structureManager->getExtension($structure->getKey(), $name);
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
