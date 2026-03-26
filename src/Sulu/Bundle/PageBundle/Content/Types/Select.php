<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\AnyOfsMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NullMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NumberMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\StringMetadata;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Metadata\PropertyMetadata as ContentPropertyMetadata;
use Sulu\Component\Content\SimpleContentType;

/**
 * ContentType for a multiple select. Currently only support for checkboxes.
 */
class Select extends SimpleContentType implements PropertyMetadataMapperInterface
{
    public function __construct()
    {
        parent::__construct('MultipleSelect', []);
    }

    public function getDefaultParams(?PropertyInterface $property = null)
    {
        return [
            'values' => new PropertyParameter('values', [], 'collection'),
        ];
    }

    public function importData(
        NodeInterface $node,
        PropertyInterface $property,
        $value,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        $property->setValue(\json_decode($value, true));
        $this->write($node, $property, $userId, $webspaceKey, $languageCode, $segmentKey);
    }

    public function mapPropertyMetadata(ContentPropertyMetadata $propertyMetadata): PropertyMetadata
    {
        $mandatory = $propertyMetadata->isRequired();

        $selectMetadata = new AnyOfsMetadata([
            new StringMetadata(),
            new NumberMetadata(),
        ]);

        if (!$mandatory) {
            $selectMetadata = new AnyOfsMetadata([
                new NullMetadata(),
                $selectMetadata,
            ]);
        }

        return new PropertyMetadata((string) $propertyMetadata->getName(), $mandatory, $selectMetadata);
    }
}
