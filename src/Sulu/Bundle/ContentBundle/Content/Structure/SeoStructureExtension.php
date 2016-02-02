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

/**
 * extends structure with seo content.
 */
class SeoStructureExtension extends AbstractExtension
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
}
