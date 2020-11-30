<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types;

use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\AnyOfsMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\EmptyStringMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NullMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMinMaxValueResolver;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\StringMetadata;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Metadata\PropertyMetadata as ContentPropertyMetadata;
use Sulu\Component\Content\SimpleContentType;

/**
 * ContentType for TextLine.
 */
class TextLine extends SimpleContentType implements PropertyMetadataMapperInterface
{
    /**
     * @var PropertyMetadataMinMaxValueResolver|null
     */
    private $propertyMetadataMinMaxValueResolver;

    public function __construct(?PropertyMetadataMinMaxValueResolver $propertyMetadataMinMaxValueResolver = null)
    {
        parent::__construct('TextLine', '');

        $this->propertyMetadataMinMaxValueResolver = $propertyMetadataMinMaxValueResolver;
    }

    public function getDefaultParams(PropertyInterface $property = null)
    {
        return [
            'headline' => new PropertyParameter('headline', false),
        ];
    }

    public function mapPropertyMetadata(ContentPropertyMetadata $propertyMetadata): PropertyMetadata
    {
        $mandatory = $propertyMetadata->isRequired();

        $minMaxValue = (object) [
            'min' => null,
            'max' => null,
        ];

        if (null !== $this->propertyMetadataMinMaxValueResolver) {
            $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue(
                $propertyMetadata,
                'min_characters',
                'max_characters'
            );
        }

        $pattern = $propertyMetadata->getParameter('pattern')['value'] ?? null;

        $textLineMetadata = new StringMetadata(
            $minMaxValue->min,
            $minMaxValue->max,
            $pattern
        );

        if (!$mandatory) {
            $textLineMetadata = new AnyOfsMetadata([
                new NullMetadata(),
                new EmptyStringMetadata(),
                $textLineMetadata,
            ]);
        }

        return new PropertyMetadata(
            $propertyMetadata->getName(),
            $propertyMetadata->isRequired(),
            $textLineMetadata
        );
    }
}
