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
    const SCHEME_PATH = '/schema/template-1.0.xsd';

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
            self::SCHEME_PATH,
            self::SCHEMA_NAMESPACE_URI
        );
    }

    public function loadFormMetadata($resource): FormMetadata
    {
        return parent::load($resource, 'form');
    }

    public function loadData($resource, \DOMXPath $xpath, $type): FormMetadata
    {
        // init running vars
        $tags = [];

        $form = new FormMetadata();
        $form->setResource($resource);

        $properties = $this->propertiesXmlParser->loadAndCreateProperties(
            $type,
            '/x:properties/x:*',
            $tags,
            $xpath,
            null,
            false
        );

        foreach ($properties as $property) {
            $form->addChild($property);
        }
        $form->burnProperties();

        return $form;
    }
}
