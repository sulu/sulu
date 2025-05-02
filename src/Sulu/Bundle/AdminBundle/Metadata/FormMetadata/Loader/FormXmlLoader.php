<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Loader;

use Sulu\Bundle\AdminBundle\Exception\InvalidRootTagException;
use Sulu\Bundle\AdminBundle\FormMetadata\FormMetadata as ExternalFormMetadata;
use Sulu\Bundle\AdminBundle\FormMetadata\FormMetadataMapper;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\PropertiesXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\SchemaXmlParser;
use Sulu\Component\Content\Metadata\Loader\AbstractLoader;

/**
 * @internal this class is not part of the public API and may be changed or removed without further notice
 *
 * @extends AbstractLoader<FormMetadata>
 */
class FormXmlLoader extends AbstractLoader
{
    public const SCHEMA_PATH = '/schema/form-1.0.xsd';

    public const SCHEMA_NAMESPACE_URI = 'http://schemas.sulu.io/template/template';

    public function __construct(
        private PropertiesXmlParser $propertiesXmlParser,
        private SchemaXmlParser $schemaXmlParser,
        private FormMetadataMapper $formMetadataMapper
    ) {
        parent::__construct(
            self::SCHEMA_PATH,
            self::SCHEMA_NAMESPACE_URI
        );
    }

    protected function parse($resource, \DOMXPath $xpath, $type): FormMetadata
    {
        if (0 === $xpath->query('/x:form')->count()) {
            throw new InvalidRootTagException($resource, 'form');
        }

        $form = new ExternalFormMetadata();
        $form->setResource($resource);
        $form->setKey($xpath->query('/x:form/x:key')->item(0)->nodeValue);
        $form->setTags($this->loadStructureTags('/x:form/x:tag', $xpath));

        $propertiesNode = $xpath->query('/x:form/x:properties')->item(0);
        $properties = $this->propertiesXmlParser->load(
            $xpath,
            $propertiesNode,
            $form->getKey()
        );

        $schemaNode = $xpath->query('/x:form/x:schema')->item(0);
        if ($schemaNode) {
            $form->setSchema($this->schemaXmlParser->load($xpath, $schemaNode));
        }

        foreach ($properties as $property) {
            $form->addChild($property);
        }
        $form->burnProperties();

        return $this->mapFormsMetadata($form);
    }

    private function mapFormsMetadata(ExternalFormMetadata $formMetadata): FormMetadata
    {
        $form = new FormMetadata();
        $form->setTags($this->formMetadataMapper->mapTags($formMetadata->getTags()));
        $form->setItems($this->formMetadataMapper->mapChildren($formMetadata->getChildren()));

        $schema = $this->formMetadataMapper->mapSchema($formMetadata->getProperties());
        $xmlSchema = $formMetadata->getSchema();
        if ($xmlSchema) {
            $schema = $schema->merge($xmlSchema);
        }

        $form->setSchema($schema);
        $form->setKey($formMetadata->getKey());

        return $form;
    }
}
