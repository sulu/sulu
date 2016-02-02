<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;

/**
 * ContentType for SingleInternalLink.
 */
class SingleInternalLink extends SimpleContentType
{
    private $template;

    public function __construct($template)
    {
        parent::__construct('SingleInternalLink', '');

        $this->template = $template;
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
        $value = $property->getValue();

        if ($node->getIdentifier() !== null && $value === $node->getIdentifier()) {
            throw new \InvalidArgumentException('Internal link node cannot reference itself');
        }

        parent::write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getReferencedUuids(PropertyInterface $property)
    {
        $uuid = $property->getValue();

        return $uuid ? [$uuid] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $value = $this->defaultValue;
        if ($node->hasProperty($property->getName())) {
            $value = $node->getPropertyValue($property->getName());
        }

        // the RedirectType subscriber sets the internal link as a reference
        if ($value instanceof NodeInterface) {
            $value = $value->getIdentifier();
        }

        $property->setValue($value);

        return $value;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
