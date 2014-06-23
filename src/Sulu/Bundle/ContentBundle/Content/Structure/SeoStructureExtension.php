<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Structure;

use PHPCR\NodeInterface;
use Sulu\Component\Content\StructureExtension\StructureExtension;

/**
 * extends structure with seo content
 * @package Sulu\Bundle\ContentBundle\Content\Structure
 */
class SeoStructureExtension extends StructureExtension
{
    /**
     * name of structure extension
     */
    const SEO_EXTENSION_NAME = 'seo';

    /**
     * {@inheritdoc}
     */
    protected $properties = array(
        'title',
        'description',
        'keywords',
        'canonicalUrl',
        'noIndex',
        'noFollow'
    );

    /**
     * {@inheritdoc}
     */
    protected $name = self::SEO_EXTENSION_NAME;

    /**
     * {@inheritdoc}
     */
    protected $additionalPrefix = 'seo';

    /**
     * {@inheritdoc}
     */
    public function save(NodeInterface $node, $data, $webspaceKey, $languageCode)
    {
        // save values
        $this->saveProperty($node, $data, 'title');
        $this->saveProperty($node, $data, 'description');
        $this->saveProperty($node, $data, 'keywords');
        $this->saveProperty($node, $data, 'canonicalUrl');
        $this->saveProperty($node, $data, 'noIndex', false);
        $this->saveProperty($node, $data, 'noFollow', false);
    }

    /**
     * save a single property value
     * @param NodeInterface $node
     * @param array $data data array
     * @param string $name name of property in node an data array
     * @param string $default value if no data exists with given name
     * @param string $default
     */
    private function saveProperty(NodeInterface $node, $data, $name, $default = '')
    {
        $value = isset($data[$name]) ? $data[$name] : $default;
        $node->setProperty($this->getPropertyName($name), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function load(NodeInterface $node, $webspaceKey, $languageCode)
    {
        return array(
            'title' => $this->loadProperty($node, 'title'),
            'description' => $this->loadProperty($node, 'description'),
            'keywords' => $this->loadProperty($node, 'keywords'),
            'canonicalUrl' => $this->loadProperty($node, 'canonicalUrl'),
            'noIndex' => $this->loadProperty($node, 'noIndex', false),
            'noFollow' => $this->loadProperty($node, 'noFollow', false)
        );
    }

    /**
     * load a single property value
     * @param NodeInterface $node
     * @param string $name name of property in node
     * @param string $default value if no property exists with given name
     * @return mixed
     */
    private function loadProperty(NodeInterface $node, $name, $default = '')
    {
        return $node->getPropertyValueWithDefault($this->getPropertyName($name), $default);
    }
}
