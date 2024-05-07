<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\FormMetadata;

use Sulu\Bundle\AdminBundle\Exception\InvalidRootTagException;
use Sulu\Bundle\AdminBundle\FormMetadata\FormMetadata as ExternalFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\LocalizedFormMetadataCollection;
use Sulu\Component\Content\Metadata\Loader\AbstractLoader;
use Sulu\Component\Content\Metadata\Parser\PropertiesXmlParser;
use Sulu\Component\Content\Metadata\Parser\SchemaXmlParser;

/**
 * Load structure from an XML file.
 */
class FormXmlLoader extends AbstractLoader
{
    public const SCHEMA_PATH = '/schema/form-1.0.xsd';

    public const SCHEMA_NAMESPACE_URI = 'http://schemas.sulu.io/template/template';

    /**
     * @param string[] $locales
     */
    public function __construct(
        private PropertiesXmlParser $propertiesXmlParser,
        private SchemaXmlParser $schemaXmlParser,
        private array $locales,
        private FormMetadataMapper $formMetadataMapper
    ) {
        $this->propertiesXmlParser = $propertiesXmlParser;
        $this->schemaXmlParser = $schemaXmlParser;
        $this->locales = $locales;
        $this->formMetadataMapper = $formMetadataMapper;

        parent::__construct(
            self::SCHEMA_PATH,
            self::SCHEMA_NAMESPACE_URI
        );
    }

    protected function parse($resource, \DOMXPath $xpath, $type): LocalizedFormMetadataCollection
    {
        // init running vars
        $tags = [];

        if (0 === $xpath->query('/x:form')->count()) {
            throw new InvalidRootTagException($resource, 'form');
        }

        $form = new ExternalFormMetadata();
        $form->setResource($resource);
        $form->setKey($xpath->query('/x:form/x:key')->item(0)->nodeValue);
        $form->setTags($this->loadStructureTags('/x:form/x:tag', $xpath));

        $propertiesNode = $xpath->query('/x:form/x:properties')->item(0);
        $properties = $this->propertiesXmlParser->load(
            $tags,
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

        $formMetadataCollection = new LocalizedFormMetadataCollection();
        foreach ($this->locales as $locale) {
            $formMetadataCollection->add($locale, $this->mapFormsMetadata($form, $locale));
        }

        return $formMetadataCollection;
    }

    private function mapFormsMetadata(ExternalFormMetadata $formMetadata, string $locale): FormMetadata
    {
        $form = new FormMetadata();
        $form->setTags($this->formMetadataMapper->mapTags($formMetadata->getTags()));
        $form->setItems($this->formMetadataMapper->mapChildren($formMetadata->getChildren(), $locale));

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
