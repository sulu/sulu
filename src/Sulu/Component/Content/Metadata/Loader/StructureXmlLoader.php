<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata\Loader;

use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolverInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Metadata\Loader\Exception\InvalidXmlException;
use Sulu\Component\Content\Metadata\Loader\Exception\RequiredPropertyNameNotFoundException;
use Sulu\Component\Content\Metadata\Loader\Exception\RequiredTagNotFoundException;
use Sulu\Component\Content\Metadata\Loader\Exception\ReservedPropertyNameException;
use Sulu\Component\Content\Metadata\Parser\PropertiesXmlParser;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\SectionMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;

/**
 * Reads a template xml and returns a StructureMetadata.
 */
class StructureXmlLoader extends AbstractLoader
{
    const SCHEME_PATH = '/schema/template-1.0.xsd';

    const SCHEMA_NAMESPACE_URI = 'http://schemas.sulu.io/template/template';

    /**
     * tags that are required in template
     * TODO should be possible to inject from config.
     *
     * @var array
     */
    private $requiredTagNames = [
        'page' => ['sulu.rlp'],
        'home' => ['sulu.rlp'],
        'snippet' => [],
    ];

    /**
     * reserved names for sulu internals
     * TODO should be possible to inject from config.
     *
     * @var array
     */
    private $reservedPropertyNames = [
        'template',
        'changer',
        'changed',
        'creator',
        'created',
        'published',
        'state',
        'internal',
        'nodeType',
        'navContexts',
        'shadow-on',
        'shadow-base',
        'author',
        'authored',
    ];

    /**
     * tags that are required in template
     * TODO should be possible to inject from config.
     *
     * @var array
     */
    private $requiredPropertyNames = [
        'title',
    ];

    /**
     * @var CacheLifetimeResolverInterface
     */
    private $cacheLifetimeResolver;

    /**
     * @var PropertiesXmlParser
     */
    private $propertiesXmlParser;

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    public function __construct(
        CacheLifetimeResolverInterface $cacheLifetimeResolver,
        PropertiesXmlParser $propertiesXmlParser,
        ContentTypeManagerInterface $contentTypeManager
    ) {
        $this->cacheLifetimeResolver = $cacheLifetimeResolver;
        $this->propertiesXmlParser = $propertiesXmlParser;
        $this->contentTypeManager = $contentTypeManager;

        parent::__construct(
            self::SCHEME_PATH,
            self::SCHEMA_NAMESPACE_URI
        );
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        if (null === $type) {
            $type = 'page';
        }

        $data = parent::load($resource, $type);

        $data = $this->normalizeStructureData($data);

        $structure = new StructureMetadata();
        $structure->setResource($resource);
        $structure->setName($data['key']);
        $structure->setCacheLifetime($data['cacheLifetime']);
        $structure->setController($data['controller']);
        $structure->setInternal($data['internal']);
        $structure->setCacheLifetime($data['cacheLifetime']);
        $structure->setAreas($data['areas']);
        $structure->setView($data['view']);
        $structure->setTags($data['tags']);
        $structure->setParameters($data['params']);

        foreach ($data['properties'] as $property) {
            $structure->addChild($property);
        }
        $structure->burnProperties();

        $this->mapMeta($structure, $data['meta']);

        return $structure;
    }

    protected function parse($resource, \DOMXPath $xpath, $type)
    {
        // init running vars
        $tags = [];

        // init result
        $result = $this->loadTemplateAttributes($resource, $xpath, $type);

        // load properties
        $result['properties'] = $this->propertiesXmlParser->loadAndCreateProperties(
            $result['key'],
            '/x:template/x:properties/x:*',
            $tags,
            $xpath
        );

        $missingProperty = $this->findMissingRequiredProperties($result['properties']);
        if ($missingProperty) {
            throw new RequiredPropertyNameNotFoundException($result['key'], $missingProperty);
        }

        $reservedProperty = $this->findReservedProperties($result['properties']);
        if ($reservedProperty) {
            throw new ReservedPropertyNameException($result['key'], $reservedProperty);
        }

        $result['properties'] = array_filter($result['properties'], function($property) {
            if (!$property instanceof PropertyMetadata) {
                return true;
            }

            $propertyType = $property->getType();
            if ($this->contentTypeManager->has($propertyType)) {
                return true;
            }

            if ('ignore' === $property->getOnInvalid()) {
                return false;
            }

            throw new \InvalidArgumentException(sprintf(
                'Content type with alias "%s" has not been registered. Known content types are: "%s"',
                $propertyType,
                implode('", "', $this->contentTypeManager->getAll())
            ));
        });

        // FIXME until excerpt-template is no page template anymore
        // - https://github.com/sulu-io/sulu/issues/1220#issuecomment-110704259
        if (!array_key_exists('internal', $result) || !$result['internal']) {
            if (isset($this->requiredTagNames[$type])) {
                foreach ($this->requiredTagNames[$type] as $requiredTagName) {
                    if (!array_key_exists($requiredTagName, $tags)) {
                        throw new RequiredTagNotFoundException($result['key'], $requiredTagName);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Load template attributes.
     */
    protected function loadTemplateAttributes($resource, \DOMXPath $xpath, $type)
    {
        if ('page' === $type || 'home' === $type) {
            $result = [
                'key' => $this->getValueFromXPath('/x:template/x:key', $xpath),
                'view' => $this->getValueFromXPath('/x:template/x:view', $xpath),
                'controller' => $this->getValueFromXPath('/x:template/x:controller', $xpath),
                'internal' => $this->getValueFromXPath('/x:template/x:internal', $xpath),
                'cacheLifetime' => $this->loadCacheLifetime('/x:template/x:cacheLifetime', $xpath),
                'tags' => $this->loadStructureTags('/x:template/x:tag', $xpath),
                'areas' => $this->loadStructureAreas('/x:template/x:areas/x:area', $xpath),
                'meta' => $this->loadMeta('/x:template/x:meta/x:*', $xpath),
            ];

            $result = array_filter(
                $result,
                function ($value) {
                    return null !== $value;
                }
            );

            foreach (['key', 'view', 'controller', 'cacheLifetime'] as $requiredProperty) {
                if (!isset($result[$requiredProperty])) {
                    throw new InvalidXmlException(
                        $type,
                        sprintf(
                            'Property "%s" is required in XML template file "%s"',
                            $requiredProperty,
                            $resource
                        )
                    );
                }
            }
        } else {
            $result = [
                'key' => $this->getValueFromXPath('/x:template/x:key', $xpath),
                'view' => $this->getValueFromXPath('/x:template/x:view', $xpath),
                'controller' => $this->getValueFromXPath('/x:template/x:controller', $xpath),
                'cacheLifetime' => $this->loadCacheLifetime('/x:template/x:cacheLifetime', $xpath),
                'tags' => $this->loadStructureTags('/x:template/x:tag', $xpath),
                'areas' => $this->loadStructureAreas('/x:template/x:areas/x:area', $xpath),
                'meta' => $this->loadMeta('/x:template/x:meta/x:*', $xpath),
            ];

            $result = array_filter(
                $result,
                function ($value) {
                    return null !== $value;
                }
            );

            if (count($result) < 1) {
                throw new InvalidXmlException($result['key']);
            }
        }

        return $result;
    }

    /**
     * Load cache lifetime metadata.
     *
     * @param $path
     * @param \DOMXPath $xpath
     *
     * @return array
     */
    private function loadCacheLifetime($path, \DOMXPath $xpath)
    {
        $nodeList = $xpath->query($path);

        if (!$nodeList->length) {
            return [
                'type' => CacheLifetimeResolverInterface::TYPE_SECONDS,
                'value' => 0,
            ];
        }

        // get first node
        $node = $nodeList->item(0);

        $type = $node->getAttribute('type');
        if ('' === $type) {
            $type = CacheLifetimeResolverInterface::TYPE_SECONDS;
        }

        $value = $node->nodeValue;
        if (!$this->cacheLifetimeResolver->supports($type, $value)) {
            throw new \InvalidArgumentException(
                sprintf('CacheLifetime "%s" with type "%s" not supported.', $value, $type)
            );
        }

        return [
            'type' => $type,
            'value' => $value,
        ];
    }

    private function normalizeStructureData($data)
    {
        $data = array_replace_recursive(
            [
                'key' => null,
                'view' => null,
                'controller' => null,
                'internal' => false,
                'cacheLifetime' => null,
                'areas' => [],
            ],
            $this->normalizeItem($data)
        );

        return $data;
    }

    private function normalizeItem($data)
    {
        $data = array_merge_recursive(
            [
                'meta' => [
                    'title' => [],
                    'info_text' => [],
                    'placeholder' => [],
                ],
                'params' => [],
                'tags' => [],
            ],
            $data
        );

        return $data;
    }

    private function mapMeta(StructureMetadata $structure, $meta)
    {
        $structure->setTitles($meta['title']);
        $structure->setDescriptions($meta['info_text']);
    }

    private function findMissingRequiredProperties(array $propertyData): ?string
    {
        foreach ($this->requiredPropertyNames as $requiredPropertyName) {
            if ($this->isRequiredPropertyMissing($propertyData, $requiredPropertyName)) {
                return $requiredPropertyName;
            }
        }

        return null;
    }

    private function isRequiredPropertyMissing(array $propertyData, string $requiredPropertyName): bool
    {
        foreach ($propertyData as $property) {
            if ($property->getName() === $requiredPropertyName) {
                return false;
            }

            if ($property instanceof SectionMetadata) {
                $isPropertyMissing = $this->findMissingRequiredProperties(
                    $property->getChildren(),
                    $requiredPropertyName
                );

                if (!$isPropertyMissing) {
                    return false;
                }
            }
        }

        return true;
    }

    private function findReservedProperties(array $propertyData): ?string
    {
        foreach ($this->reservedPropertyNames as $reservedPropertyName) {
            if ($this->isReservedProperty($propertyData, $reservedPropertyName)) {
                return $reservedPropertyName;
            }
        }

        return null;
    }

    private function isReservedProperty(array $propertyData, string $reservedPropertyName): bool
    {
        foreach ($propertyData as $property) {
            if ($property->getName() === $reservedPropertyName) {
                return true;
            }

            if ($property instanceof SectionMetadata) {
                $isReservedProperty = $this->isReservedProperty(
                    $property->getChildren(),
                    $reservedPropertyName
                );

                if ($isReservedProperty) {
                    return true;
                }
            }
        }

        return false;
    }
}
