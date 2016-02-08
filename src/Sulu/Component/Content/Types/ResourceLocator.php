<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Types\Rlp\Strategy\RLPStrategyInterface;
use Sulu\Component\Content\SimpleContentType;

/**
 * Class ResourceLocator.
 */
class ResourceLocator extends SimpleContentType
{
    /**
     * template for form generation.
     *
     * @var string
     */
    private $template;

    public function __construct($template)
    {
        $this->template = $template;
    }

    /**
     * Returns the name of the content type.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function readForPreview($data, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function hasValue(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function remove(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
    }

    public function getTemplate() 
    {
        return $this->template;
    }
    
}
