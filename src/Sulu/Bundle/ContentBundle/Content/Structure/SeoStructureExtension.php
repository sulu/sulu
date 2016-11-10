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

    protected $seoAttributes = [
        'hideInSitemap',
        'noIndex',
        'noFollow',
        'canonicalUrl',
    ];

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
        $data = [];
        foreach ($properties as $key => $property) {
            $value = $property;
            if (is_bool($value)) {
                $value = (int) $value;
            }

            $data[$key] = [
                'name' => $key,
                'value' => $value,
                'type' => '',
                'options' => $this->getExportOption($key, $format),
            ];
        }

        return $data;
    }

    /**
     * @param $key
     * @param $format
     *
     * @return array
     */
    protected function getExportOption($key, $format)
    {
        if ('1.2.xliff' !== $format) {
            return;
        }

        $translate = true;

        if (in_array(
            $key,
            $this->seoAttributes
        )) {
            $translate = false;
        }

        return [
            'translate' => $translate,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getImportPropertyNames()
    {
        return $this->properties;
    }

    /**
     * {@inheritdoc}
     */
    public function import(NodeInterface $node, $data, $webspaceKey, $languageCode, $format)
    {
        $this->setLanguageCode($languageCode, 'i18n', null);

        $this->convertCheckboxData($data, 'noIndex');
        $this->convertCheckboxData($data, 'noFollow');
        $this->convertCheckboxData($data, 'hideInSitemap');

        $this->save($node, $data, $webspaceKey, $languageCode);
    }

    /**
     * {@inheritdoc}
     */
    protected function convertCheckboxData(&$data, $key, $default = false)
    {
        if ($data[$key] === '0') {
            $data[$key] = false;

            return;
        }

        if ($data[$key] === '1') {
            $data[$key] = true;

            return;
        }

        $data[$key] = $default;
    }
}
