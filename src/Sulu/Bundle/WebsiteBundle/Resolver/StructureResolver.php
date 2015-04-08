<?php

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Sulu\Component\Content\Type\ContentTypeManagerInterface;
use Sulu\Component\Content\Structure\Page;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Structure\Factory\StructureFactoryInterface;

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
     * @var StructureFactoryInterface
     */
    protected $structureFactory;

    /**
     * @param ContentTypeManagerInterface $contentTypeManager
     * @param StructureFactoryInterface $structureFactory
     */
    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        StructureFactoryInterface $structureFactory
    ) {
        $this->contentTypeManager = $contentTypeManager;
        $this->structureFactory = $structureFactory;
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
            'template' => $structure->getKey(),
            'path' => $structure->getPath(),
        );

        if ($structure instanceof Page) {
            $data['extension'] = $structure->getExt();
            $data['urls'] = $structure->getUrls();
            $data['published'] = $structure->getPublished();

            foreach ($data['extension'] as $name => $value) {
                $extension = $this->structureFactory->getExtension($structure->getKey(), $name);
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
