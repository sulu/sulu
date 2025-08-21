<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Admin\PropertyMetadataMapper;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\AnyOfsMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NullMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ObjectMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\StringMetadata;

/**
 * @internal use symfony dependency injection container to override the service if you want to change the behavior
 */
final readonly class PageTreeRoutePropertyMetadataMapper implements PropertyMetadataMapperInterface
{
    public function mapPropertyMetadata(FieldMetadata $fieldMetadata): PropertyMetadata
    {
        $mandatory = $fieldMetadata->isRequired();

        $pageMetadata = new ObjectMetadata([
            new PropertyMetadata('uuid', $mandatory, new StringMetadata()),
            new PropertyMetadata('path', $mandatory, new StringMetadata()),
        ]);

        if (!$mandatory) {
            $pageMetadata = new AnyOfsMetadata([
                new NullMetadata(),
                $pageMetadata,
            ]);
        }

        $pageTreeRouteMetadata = new ObjectMetadata([
            new PropertyMetadata('page', $mandatory, $pageMetadata),
            new PropertyMetadata('path', false, new StringMetadata()),
            new PropertyMetadata('suffix', false, new StringMetadata()),
        ]);

        if (!$mandatory) {
            $pageTreeRouteMetadata = new AnyOfsMetadata([
                new NullMetadata(),
                $pageTreeRouteMetadata,
            ]);
        }

        return new PropertyMetadata($fieldMetadata->getName(), $mandatory, $pageTreeRouteMetadata);
    }
}
