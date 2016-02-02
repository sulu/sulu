<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Bridge;

use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Sulu\Component\DocumentManager\PropertyEncoder as BasePropertyEncoder;

/**
 * This class infers information about documents, for example
 * the documents locale, webspace, path, etc.
 */
class PropertyEncoder extends BasePropertyEncoder
{
    /**
     * @param NamespaceRegistry $namespaceRegistry
     */
    public function __construct(NamespaceRegistry $namespaceRegistry)
    {
        parent::__construct($namespaceRegistry);
    }

    public function fromProperty(PropertyMetadata $property, $locale = null)
    {
        if (true === $property->isLocalized()) {
            return $this->localizedContentName($property->getName(), $locale);
        }

        return $this->contentname($property->getName());
    }
}
