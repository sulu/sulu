<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Structure;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Extension\AbstractExtension;
use Sulu\Component\Content\Extension\ExportExtensionInterface;

/**
 * extends structure with seo content.
 */
class SeoStructureExtension extends AbstractExtension implements ExportExtensionInterface
{
    /**
     * name of structure extension.
     */
    const SEO_EXTENSION_NAME = 'seo';

    /**
     * {@inheritdoc}
     */
    protected $properties = [
        'title',
        'description',
        'keywords',
        'canonicalUrl',
        'noIndex',
        'noFollow',
        'hideInSitemap',
    ];

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
        $this->saveProperty($node, $data, 'title');
        $this->saveProperty($node, $data, 'description');
        $this->saveProperty($node, $data, 'keywords');
        $this->saveProperty($node, $data, 'canonicalUrl');
        $this->saveProperty($node, $data, 'noIndex', false);
        $this->saveProperty($node, $data, 'noFollow', false);
        $this->saveProperty($node, $data, 'hideInSitemap', false);
    }

    /**
     * {@inheritdoc}
     */
    public function load(NodeInterface $node, $webspaceKey, $languageCode)
    {
        return [
            'title' => $this->loadProperty($node, 'title'),
            'description' => $this->loadProperty($node, 'description'),
            'keywords' => $this->loadProperty($node, 'keywords'),
            'canonicalUrl' => $this->loadProperty($node, 'canonicalUrl'),
            'noIndex' => $this->loadProperty($node, 'noIndex', false),
            'noFollow' => $this->loadProperty($node, 'noFollow', false),
            'hideInSitemap' => $this->loadProperty($node, 'hideInSitemap', false),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function export($properties, $format = null)
    {
        $data = array();

        foreach ($properties as $key => $property) {
            $value = $property;
            if (is_bool($value)) {
                $value = (int) $value;
            }

            $data[$key]= array(
                'name' => self::SEO_EXTENSION_NAME . '-' . $key,
                'value' => $value,
                'options' => $this->getExportOption($key, $format)
            );
        }

        return $data;
    }

    /**
     * @param $key
     * @param $format
     * @return null
     */
    protected function getExportOption($key, $format)
    {
        if ($format == '1.2.xliff') {
            $translate = true;

            if (in_array(
                $key,
                array(
                    'hideInSitemap',
                    'noIndex',
                    'noFollow',
                    'canonicalUrl',
                )
            )) {
                $translate = false;
            }

            return array(
                'translate' => $translate,
            );
        }

        return null;
    }
}
