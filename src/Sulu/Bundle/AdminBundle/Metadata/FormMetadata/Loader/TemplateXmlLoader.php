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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\MetaXmlParser;
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
class TemplateXmlLoader extends AbstractLoader
{
    use XmlParserTrait;

    /**
     * @var string
     */
    public const SCHEMA_PATH = '/schema/template-1.0.xsd';

    /**
     * @var string
     */
    public const SCHEMA_NAMESPACE_URI = 'http://schemas.sulu.io/template/template';

    public function __construct(
        private PropertiesXmlParser $propertiesXmlParser,
        private SchemaXmlParser $schemaXmlParser,
        private TagXmlParser $tagXmlParser,
        private MetaXmlParser $metaXmlParser,
        private SchemaMetadataProvider $schemaMetadataProvider,
    ) {
        parent::__construct(
            self::SCHEMA_PATH,
            self::SCHEMA_NAMESPACE_URI
        );
    }

    protected function parse(string $resource, \DOMXPath $xpath, ?string $type): FormMetadata
    {
        if (0 === $xpath->query('/x:template')->count()) {
            throw new InvalidRootTagException($resource, 'template');
        }

        $form = new FormMetadata();
        $form->addResource($resource);
        $templateKey = $this->getValueFromXPath('/x:template/x:key', $xpath);
        \assert(\is_string($templateKey), 'Expected the template key of "' . $resource . '" to be defined.');
        $form->setKey($templateKey);

        $tagNodes = $xpath->query('/x:template/x:tag') ?: [];
        $form->setTags($this->tagXmlParser->load($xpath, $tagNodes));

        $templateNode = ($xpath->query('/x:template') ?: null)?->item(0);
        \assert(null !== $templateNode, 'Expected <template> be defined for "' . $resource . '".');
        $meta = $this->metaXmlParser->load($xpath, $templateNode);

        if (\array_key_exists('title', $meta)) {
            $form->setTitles($meta['title']);
        }

        $propertiesNode = ($xpath->query('/x:template/x:properties') ?: null)?->item(0);
        \assert(null !== $propertiesNode, 'Expected <properties> be defined for "' . $resource . '".');
        $properties = $this->propertiesXmlParser->load(
            $xpath,
            $propertiesNode,
            $form->getKey()
        );

        $schema = $this->schemaMetadataProvider->getMetadata($properties);
        $schemaNode = $xpath->query('/x:template/x:schema')->item(0);

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
