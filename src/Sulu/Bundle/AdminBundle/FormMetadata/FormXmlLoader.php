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

use Sulu\Bundle\AdminBundle\FormMetadata\FormMetadata as ExternalFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Component\Content\Metadata\Loader\AbstractLoader;
use Sulu\Component\Content\Metadata\Parser\PropertiesXmlParser;
use Sulu\Component\Content\Metadata\Parser\SchemaXmlParser;
use Symfony\Component\Config\Resource\FileResource;

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

    /**
     * @var string[]
     */
    private $locales;

    /**
     * @var FormMetadataMapper
     */
    private $formMetadataMapper;

    /**
     * FormXmlLoader constructor.
     *
     * @param PropertiesXmlParser $propertiesXmlParser
     * @param SchemaXmlParser $schemaXmlParser
     * @param array $locales
     * @param FormMetadataMapper $formMetadataMapper
     */
    public function __construct(
        PropertiesXmlParser $propertiesXmlParser,
        SchemaXmlParser $schemaXmlParser,
        array $locales,
        FormMetadataMapper $formMetadataMapper
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

    protected function parse($resource, \DOMXPath $xpath, $type): FormMetadata
    {
        // init running vars
        $tags = [];
        $newForm = null;

        $form = new ExternalFormMetadata();
        $formKey = $xpath->query('/x:form/x:key')->item(0)->nodeValue;
        $form->setResource($resource);
        $form->setKey($formKey);

        $propertiesNode = $xpath->query('/x:form/x:properties')->item(0);
        $properties = $this->propertiesXmlParser->load(
            $tags,
            $xpath,
            $propertiesNode
        );

        $schemaNode = $xpath->query('/x:form/x:schema')->item(0);
        if ($schemaNode) {
            $form->setSchema($this->schemaXmlParser->load($xpath, $schemaNode));
        }

        foreach ($properties as $property) {
            $form->addChild($property);
        }
        $form->burnProperties();

        foreach ($this->locales as $locale) {
            $newForm = $this->mapFormsMetadata($form, $locale);
            $configCache = $this->formMetadataMapper->getConfigCache($formKey, $locale);
            $configCache->write(
                serialize($newForm),
                array_map(function(ExternalFormMetadata $form) {
                    return new FileResource($form->getResource());
                }, [$form])
            );
        }

        return $newForm;
    }

    /**
     * @param $formMetadata
     * @param $locale
     *
     * @return FormMetadata
     *
     * @throws \Exception
     */
    private function mapFormsMetadata($formMetadata, $locale)
    {
        $form = new FormMetadata();
        $this->formMetadataMapper->mapChildren($formMetadata->getChildren(), $form, $locale);

        $schema = $this->formMetadataMapper->mapSchema($formMetadata->getProperties());
        $formSchema = $formMetadata->getSchema();
        if ($formSchema) {
            $schema = $schema->merge($formSchema);
        }

        $form->setSchema($schema);
        $form->setKey($formMetadata->getKey());

        return $form;
    }
}
