<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata\Provider;

use Metadata\MetadataFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\ProviderInterface;

/**
 * Provides metadata with the given metadata-factory.
 */
class MetadataProvider implements ProviderInterface
{
    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    public function __construct(MetadataFactoryInterface $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataForClass($className)
    {
        return $this->metadataFactory->getMetadataForClass($className);
    }
}
