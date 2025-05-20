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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\PropertiesXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\SchemaXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\TagXmlParser;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SchemaMetadataProvider;
use Sulu\Bundle\AdminBundle\Metadata\XmlParserTrait;

/**
 * @internal this class is not part of the public API and may be changed or removed without further notice
 *
 * @extends AbstractLoader<FormMetadata>
 */
class FormXmlLoader extends AbstractLoader
{
    use XmlParserTrait;

    public const SCHEMA_PATH = '/schema/form-1.0.xsd';

    public const SCHEMA_NAMESPACE_URI = 'http://schemas.sulu.io/template/template';

    public function __construct(
        private PropertiesXmlParser $propertiesXmlParser,
        private SchemaXmlParser $schemaXmlParser,
        private TagXmlParser $tagXmlParser,
        private SchemaMetadataProvider $schemaMetadataProvider,
    ) {
        parent::__construct(
            self::SCHEMA_PATH,
            self::SCHEMA_NAMESPACE_URI
        );
    }

    protected function parse(string $resource, \DOMXPath $xpath, ?string $type): FormMetadata
    {
        if (0 === $xpath->query('/x:form')->count()) {
            throw new InvalidRootTagException($resource, 'form');
        }

        $form = new FormMetadata();
        $form->addResource($resource);
        $formKey = $this->getValueFromXPath('/x:form/x:key', $xpath);
        \assert(\is_string($formKey), 'Expected the form key of "' . $resource . '" to be defined.');
        $form->setKey($formKey);

        $tagNodes = $xpath->query('/x:form/x:tag') ?: [];
        $form->setTags($this->tagXmlParser->load($xpath, $tagNodes));

        $propertiesNode = ($xpath->query('/x:form/x:properties') ?: null)?->item(0);
        \assert(null !== $propertiesNode, 'Expected properties be defined for "' . $resource . '".');
        $properties = $this->propertiesXmlParser->load(
            $xpath,
            $propertiesNode,
            $form->getKey()
        );

        $schema = $this->schemaMetadataProvider->getMetadata($properties);
        $schemaNode = $xpath->query('/x:form/x:schema')->item(0);

        if ($schemaNode) {
            $schema = $schema->merge($this->schemaXmlParser->load($xpath, $schemaNode));
        }
        $form->setSchema($schema);

        foreach ($properties as $property) {
            $form->addItem($property);
        }

        return $form;
    }
}
