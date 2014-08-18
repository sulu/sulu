<?php

namespace Sulu\Component\Content;

use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureInterface;

class StructureViewResolver
{
    protected $contentTypeManager;

    public function __construct(ContentTypeManagerInterface $contentTypeManager)
    {
        $this->contentTypeManager = $contentTypeManager;
    }

    public function resolve(StructureInterface $structure)
    {
        $data = array();

        foreach ($structure->getProperties() as $property) {
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());
            $data[$property->getName()] = $contentType->getViewData($property);
        }

        return $data;
    }
}
