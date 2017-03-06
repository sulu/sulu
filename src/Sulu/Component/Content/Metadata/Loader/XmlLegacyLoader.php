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

use Exception;
use Sulu\Component\Content\Metadata\Loader\Exception\InvalidXmlException;
use Sulu\Component\Content\Metadata\Loader\Exception\RequiredPropertyNameNotFoundException;
use Sulu\Component\Content\Metadata\Loader\Exception\RequiredTagNotFoundException;
use Sulu\Component\Content\Metadata\Loader\Exception\ReservedPropertyNameException;
use Sulu\Component\HttpCache\CacheLifetimeResolverInterface;
use Sulu\Exception\FeatureNotImplementedException;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * reads a template xml and returns a array representation.
 */
class XmlLegacyLoader implements LoaderInterface
{
    const SCHEME_PATH = '/schema/template-1.0.xsd';

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
     * @var CacheLifetimeResolverInterface
     */
    private $cacheLifetimeResolver;

    /**
     * @param CacheLifetimeResolverInterface $cacheLifetimeResolver
     */
    public function __construct(CacheLifetimeResolverInterface $cacheLifetimeResolver)
    {
        $this->cacheLifetimeResolver = $cacheLifetimeResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = 'page')
    {
        // init running vars
        $tags = [];

        $schemaPath = __DIR__ . static::SCHEME_PATH;

        $cwd = getcwd();
        // Necessary only for Windows, no effect on linux. Mute errors for PHP with chdir disabled to avoid E_WARNINGs
        @chdir(dirname($resource));

        // read file
        $xmlDocument = XmlUtils::loadFile(
            $resource,
            function (\DOMDocument $dom) use ($resource, $schemaPath) {
                $dom->documentURI = $resource;
                $dom->xinclude();

                return @$dom->schemaValidate($schemaPath);
            }
        );

        // Necessary only for Windows, no effect on linux. Mute errors for PHP with chdir disabled to avoid E_WARNINGs
        @chdir($cwd);

        // generate xpath for file
        $xpath = new \DOMXPath($xmlDocument);
        $xpath->registerNamespace('x', 'http://schemas.sulu.io/template/template');

        // init result
        $result = $this->loadTemplateAttributes($resource, $xpath, $type);

        // load properties
        $result['properties'] = $this->loadProperties($result['key'], '/x:template/x:properties/x:*', $tags, $xpath);

        // check if required properties are existing
        foreach ($this->requiredPropertyNames as $requiredPropertyName) {
            $requiredPropertyNameFound = false;
            if (array_key_exists($requiredPropertyName, $result['properties'])) {
                $requiredPropertyNameFound = true;
            }

            // check all section properties as well
            foreach ($result['properties'] as $property) {
                if (!$requiredPropertyNameFound
                    && $property['type'] == 'section'
                    && array_key_exists($requiredPropertyName, $property['properties'])
                ) {
                    $requiredPropertyNameFound = true;
                }
            }

            if (!$requiredPropertyNameFound) {
                throw new RequiredPropertyNameNotFoundException($result['key'], $requiredPropertyName);
            }
        }

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
     * load basic template attributes.
     */
    private function loadTemplateAttributes($resource, \DOMXPath $xpath, $type)
    {
        if ($type === 'page' || $type === 'home') {
            $result = [
                'key' => $this->getValueFromXPath('/x:template/x:key', $xpath),
                'view' => $this->getValueFromXPath('/x:template/x:view', $xpath),
                'controller' => $this->getValueFromXPath('/x:template/x:controller', $xpath),
                'internal' => $this->getValueFromXPath('/x:template/x:internal', $xpath),
                'cacheLifetime' => $this->loadCacheLifetime('/x:template/x:cacheLifetime', $xpath),
                'tags' => $this->loadStructureTags('/x:template/x:tag', $xpath),
                'meta' => $this->loadMeta('/x:template/x:meta/x:*', $xpath),
            ];

            $result = array_filter(
                $result,
                function ($value) {
                    return $value !== null;
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
                'meta' => $this->loadMeta('/x:template/x:meta/x:*', $xpath),
            ];

            $result = array_filter($result);

            if (count($result) < 1) {
                throw new InvalidXmlException($result['key']);
            }
        }

        return $result;
    }

    /**
     * load properties from given context.
     */
    private function loadProperties($templateKey, $path, &$tags, \DOMXPath $xpath, \DOMNode $context = null)
    {
        $result = [];

        /** @var \DOMElement $node */
        foreach ($xpath->query($path, $context) as $node) {
            if ($node->tagName === 'property') {
                $value = $this->loadProperty($templateKey, $xpath, $node, $tags);
                $result[$value['name']] = $value;
            } elseif ($node->tagName === 'block') {
                $value = $this->loadBlock($templateKey, $xpath, $node, $tags);
                $result[$value['name']] = $value;
            } elseif ($node->tagName === 'section') {
                $value = $this->loadSection($templateKey, $xpath, $node, $tags);
                $result[$value['name']] = $value;
            }
        }

        return $result;
    }

    /**
     * load single property.
     */
    private function loadProperty($templateKey, \DOMXPath $xpath, \DOMNode $node, &$tags)
    {
        $result = $this->loadValues(
            $xpath,
            $node,
            ['name', 'type', 'minOccurs', 'maxOccurs', 'colspan', 'cssClass']
        );

        if (in_array($result['name'], $this->reservedPropertyNames, false)) {
            throw new ReservedPropertyNameException($templateKey, $result['name']);
        }

        $result['mandatory'] = $this->getValueFromXPath('@mandatory', $xpath, $node, false);
        $result['multilingual'] = $this->getValueFromXPath('@multilingual', $xpath, $node, true);
        $result['tags'] = $this->loadTags('x:tag', $tags, $xpath, $node);
        $result['params'] = $this->loadParams('x:params/x:param', $xpath, $node);
        $result['meta'] = $this->loadMeta('x:meta/x:*', $xpath, $node);

        return $result;
    }

    /**
     * load single block.
     */
    private function loadBlock($templateKey, \DOMXPath $xpath, \DOMNode $node, &$tags)
    {
        $result = $this->loadValues(
            $xpath,
            $node,
            ['name', 'default-type', 'minOccurs', 'maxOccurs', 'colspan', 'cssClass']
        );

        $result['mandatory'] = $this->getValueFromXPath('@mandatory', $xpath, $node, false);
        $result['type'] = 'block';
        $result['tags'] = $this->loadTags('x:tag', $tags, $xpath, $node);
        $result['params'] = $this->loadParams('x:params/x:param', $xpath, $node);
        $result['meta'] = $this->loadMeta('x:meta/x:*', $xpath, $node);
        $result['types'] = $this->loadTypes($templateKey, 'x:types/x:type', $tags, $xpath, $node);

        return $result;
    }

    /**
     * load single block.
     */
    private function loadSection($templateKey, \DOMXPath $xpath, \DOMNode $node, &$tags)
    {
        $result = $this->loadValues(
            $xpath,
            $node,
            ['name', 'colspan', 'cssClass']
        );

        $result['type'] = 'section';
        $result['params'] = $this->loadParams('x:params/x:param', $xpath, $node);
        $result['meta'] = $this->loadMeta('x:meta/x:*', $xpath, $node);
        $result['properties'] = $this->loadProperties($templateKey, 'x:properties/x:*', $tags, $xpath, $node);

        return $result;
    }

    /**
     * load tags from given tag and validates them.
     */
    private function loadTags($path, &$tags, \DOMXPath $xpath, \DOMNode $context = null)
    {
        $result = [];

        /** @var \DOMElement $node */
        foreach ($xpath->query($path, $context) as $node) {
            $tag = $this->loadTag($xpath, $node);
            $this->validateTag($tag, $tags);

            $result[] = $tag;
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

    /**
     * Loads the tags for the structure.
     *
     * @param $path
     * @param $xpath
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    private function loadStructureTags($path, $xpath)
    {
        $result = [];

        foreach ($xpath->query($path) as $node) {
            $tag = [
                'name' => null,
                'attributes' => [],
            ];

            foreach ($node->attributes as $key => $attr) {
                if (in_array($key, ['name'])) {
                    $tag[$key] = $attr->value;
                } else {
                    $tag['attributes'][$key] = $attr->value;
                }
            }

            if (!isset($tag['name'])) {
                // this should not happen because of the XSD validation
                throw new \InvalidArgumentException('Tag does not have a name in template definition');
            }

            $result[] = $tag;
        }

        return $result;
    }

    /**
     * validates a single tag.
     */
    private function validateTag($tag, &$tags)
    {
        if (!isset($tags[$tag['name']])) {
            $tags[$tag['name']] = [];
        }

        $tags[$tag['name']][] = $tag['priority'];
    }

    /**
     * load single tag.
     */
    private function loadTag(\DOMXPath $xpath, \DOMNode $node)
    {
        $tag = [
            'name' => null,
            'priority' => null,
            'attributes' => [],
        ];

        foreach ($node->attributes as $key => $attr) {
            if (in_array($key, ['name', 'priority'])) {
                $tag[$key] = $attr->value;
            } else {
                $tag['attributes'][$key] = $attr->value;
            }
        }

        return $tag;
    }

    /**
     * load params from given node.
     */
    private function loadParams($path, \DOMXPath $xpath, \DOMNode $context = null)
    {
        $result = [];

        /** @var \DOMElement $node */
        foreach ($xpath->query($path, $context) as $node) {
            $result[] = $this->loadParam($xpath, $node);
        }

        return $result;
    }

    /**
     * load single param.
     */
    private function loadParam(\DOMXPath $xpath, \DOMNode $node)
    {
        $name = $this->getValueFromXPath('@name', $xpath, $node, 'string');
        $type = $this->getValueFromXPath('@type', $xpath, $node, 'string');
        $meta = $this->loadMeta('x:meta/x:*', $xpath, $node);

        switch ($type) {
            case 'collection':
                $value = $this->loadParams('x:param', $xpath, $node);
                break;
            default:
                $value = $this->getValueFromXPath('@value', $xpath, $node, 'string');
                break;
        }

        return [
            'name' => $name,
            'value' => $value,
            'type' => $type,
            'meta' => $meta,
        ];
    }

    /**
     * load types from given node.
     */
    private function loadTypes($templateKey, $path, &$tags, \DOMXPath $xpath, \DOMNode $context = null)
    {
        $result = [];

        /** @var \DOMElement $node */
        foreach ($xpath->query($path, $context) as $node) {
            $value = $this->loadType($templateKey, $xpath, $node, $tags);
            $result[$value['name']] = $value;
        }

        return $result;
    }

    /**
     * load single param.
     */
    private function loadType($templateKey, \DOMXPath $xpath, \DOMNode $node, &$tags)
    {
        $result = $this->loadValues($xpath, $node, ['name']);

        $result['meta'] = $this->loadMeta('x:meta/x:*', $xpath, $node);
        $result['properties'] = $this->loadProperties($templateKey, 'x:properties/x:*', $tags, $xpath, $node);

        return $result;
    }

    private function loadMeta($path, \DOMXPath $xpath, \DOMNode $context = null)
    {
        $result = [];

        /** @var \DOMElement $node */
        foreach ($xpath->query($path, $context) as $node) {
            $attribute = $node->tagName;
            $lang = $this->getValueFromXPath('@lang', $xpath, $node);

            if (!isset($result[$node->tagName])) {
                $result[$attribute] = [];
            }
            $result[$attribute][$lang] = $node->textContent;
        }

        return $result;
    }

    /**
     * load values defined by key from given node.
     */
    private function loadValues(\DOMXPath $xpath, \DOMNode $node, $keys, $prefix = '@')
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->getValueFromXPath($prefix . $key, $xpath, $node);
        }

        return $result;
    }

    /**
     * returns value of path.
     */
    private function getValueFromXPath($path, \DOMXPath $xpath, \DomNode $context = null, $default = null)
    {
        try {
            $result = $xpath->query($path, $context);
            if ($result->length === 0) {
                return $default;
            }

            $item = $result->item(0);
            if ($item === null) {
                return $default;
            }

            if ('true' === $item->nodeValue) {
                return true;
            }

            if ('false' === $item->nodeValue) {
                return false;
            }

            return $item->nodeValue;
        } catch (Exception $ex) {
            return $default;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function getResolver()
    {
        throw new FeatureNotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function setResolver(LoaderResolverInterface $resolver)
    {
        throw new FeatureNotImplementedException();
    }
}
