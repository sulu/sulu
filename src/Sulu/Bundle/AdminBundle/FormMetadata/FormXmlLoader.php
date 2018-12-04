<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\FormMetadata;

use Sulu\Component\Content\Metadata\Loader\AbstractLoader;
use Sulu\Component\Content\Metadata\Parser\PropertiesXmlParser;
use Sulu\Component\Content\Metadata\Parser\SchemaXmlParser;

/**
 * Load structure from an XML file.
 */
class FormXmlLoader extends AbstractLoader
{
    const SCHEMA_PATH = '/schema/form-1.0.xsd';

    const SCHEMA_NAMESPACE_URI = 'http://schemas.sulu.io/template/template';

    /**
     * @var PropertiesXmlParser
     */
    private $propertiesXmlParser;

    /**
     * @var SchemaXmlParser
     */
    private $schemaXmlParser;

    public function __construct(
        PropertiesXmlParser $propertiesXmlParser,
        SchemaXmlParser $schemaXmlParser
    ) {
        $this->propertiesXmlParser = $propertiesXmlParser;
        $this->schemaXmlParser = $schemaXmlParser;

        parent::__construct(
            self::SCHEMA_PATH,
            self::SCHEMA_NAMESPACE_URI
        );
    }

    protected function parse($resource, \DOMXPath $xpath, $type): FormMetadata
    {
        // init running vars
        $tags = [];

        $form = new FormMetadata();
        $form->setResource($resource);
        $form->setKey($xpath->query('/x:form/x:key')->item(0)->nodeValue);
        $form->setResourceKey($xpath->query('/x:form/x:resourceKey')->item(0)->nodeValue);

        $properties = $this->propertiesXmlParser->load(
            '/x:form/x:properties/x:*',
            $tags,
            $xpath
        );

        $schemaNode = $xpath->query('/x:form/x:schema')->item(0);
        if ($schemaNode) {
            $form->setSchema($this->schemaXmlParser->load($xpath, $schemaNode));
        }

        foreach ($properties as $property) {
            $form->addChild($property);
        }
        $form->burnProperties();

        return $form;
    }
}
