<?php

namespace Sulu\Component\Content;

use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureInterface;

/**
 * Class that "resolves" the view data for a given structure.
 */
class StructureViewResolver
{
    protected $contentTypeManager;

    /**
     * @param ContentTypeManagerInterface $contentTypeManager
     */
    public function __construct(ContentTypeManagerInterface $contentTypeManager)
    {
        $this->contentTypeManager = $contentTypeManager;
    }

    /**
     * Resolve the given Structure into an array each element of which
     * correspnds to a property and the data produced by that elements
     * content type.
     *
     * @param StructureInterface $structure
     */
    public function resolve(StructureInterface $structure)
    {
        $data = array();

        foreach ($structure->getProperties(true) as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());
            $data[$property->getName()] = $contentType->getViewData($property);
        }

        return $data;
    }
}
