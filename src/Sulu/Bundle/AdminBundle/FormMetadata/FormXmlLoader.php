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

    public function __construct(
        PropertiesXmlParser $propertiesXmlParser
    ) {
        $this->propertiesXmlParser = $propertiesXmlParser;

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

        $properties = $this->propertiesXmlParser->loadAndCreateProperties(
            $type,
            '/x:form/x:properties/x:*',
            $tags,
            $xpath
        );

        foreach ($properties as $property) {
            $form->addChild($property);
        }
        $form->burnProperties();

        return $form;
    }
}
