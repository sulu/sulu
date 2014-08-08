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
class ExcerptStructureExtension extends StructureExtension
{
    /**
     * name of structure extension
     */
    const EXCERPT_EXTENSION_NAME = 'excerpt';

    /**
     * {@inheritdoc}
     */
    protected $properties = array(
        'title',
        'description',
        'images'
    );

    /**
     * {@inheritdoc}
     */
    protected $name = self::EXCERPT_EXTENSION_NAME;

    /**
     * {@inheritdoc}
     */
    protected $additionalPrefix = 'excerpt';

    /**
     * {@inheritdoc}
     */
    public function save(NodeInterface $node, $data, $webspaceKey, $languageCode)
    {
        $this->saveProperty($node, $data, 'title');
        $this->saveProperty($node, $data, 'description');

        $value = isset($data['images']) ? $data['images'] : array();
        $node->setProperty($this->getPropertyName('images'), json_encode($value));

        $this->load($node, $webspaceKey, $languageCode);
    }

    /**
     * {@inheritdoc}
     */
    public function load(NodeInterface $node, $webspaceKey, $languageCode)
    {
        $this->data = array(
            'title' => $this->loadProperty($node, 'title'),
            'description' => $this->loadProperty($node, 'description'),
            'images' => json_decode(
                $this->loadProperty(
                    $node,
                    'images',
                    json_encode(
                        array(
                            'displayOption' => 'left',
                            'ids' => array(),
                            'config' => array()
                        )
                    )
                ),
                true
            )
        );
    }
}
