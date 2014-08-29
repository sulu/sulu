<?php

namespace Sulu\Component\Content;

/**
 * Class that "resolves" the content data for a given structure.
 */
class StructureContentResolver
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
     * @return array
     */
    public function resolve(StructureInterface $structure)
    {
        $data = array();

        foreach ($structure->getProperties(true) as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());
            $data[$property->getName()] = $contentType->getContentData($property);
        }

        return $data;
    }
}
