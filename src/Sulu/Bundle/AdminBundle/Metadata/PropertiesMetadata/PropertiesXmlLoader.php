<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\PropertiesMetadata;

use Sulu\Component\Content\Metadata\Loader\AbstractLoader;
use Sulu\Component\Content\Metadata\Parser\PropertiesXmlParser;
use Sulu\Component\Content\Metadata\PropertiesMetadata;

class PropertiesXmlLoader extends AbstractLoader
{
    const SCHEMA_PATH = '/schema/template-1.0.xsd';

    const SCHEMA_NAMESPACE_URI = 'http://schemas.sulu.io/template/template';

    /**
     * @var PropertiesXmlParser
     */
    private $propertiesXmlParser;

    public function __construct(
        PropertiesXmlParser $propertiesXmlParser
    ) {
        $this->propertiesXmlParser = $propertiesXmlParser;
        parent::__construct(
            self::SCHEMA_PATH,
            self::SCHEMA_NAMESPACE_URI
        );
    }

    protected function parse($resource, \DOMXPath $xpath, $type): PropertiesMetadata
    {
        $tags = [];

        $propertiesMetadata = new PropertiesMetadata();
        $propertiesMetadata->setResource($resource);

        $propertiesNode = $xpath->query('/x:properties')->item(0);
        $properties = $this->propertiesXmlParser->load(
            $tags,
            $xpath,
            $propertiesNode
        );

        foreach ($properties as $property) {
            $propertiesMetadata->addChild($property);
        }
        $propertiesMetadata->burnProperties();

        return $propertiesMetadata;
    }
}
