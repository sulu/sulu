<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Infrastructure\Sulu\ListBuilder;

use Sulu\Component\Rest\ListBuilder\Expression\ExpressionInterface;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Filter\FilterTypeInterface;
use Sulu\Component\Rest\ListBuilder\Filter\InvalidFilterTypeOptionsException;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;

/**
 * Filters media by content language, plus a value matching media without any language set.
 */
class MediaLanguageFilterType implements FilterTypeInterface
{
    /**
     * Matches media with no language set, via IS NULL on the LEFT join.
     */
    public const NONE_VALUE = '_none';

    public function filter(
        ListBuilderInterface $listBuilder,
        FieldDescriptorInterface $fieldDescriptor,
        mixed $options
    ): void {
        if (!\is_string($options)) {
            throw new InvalidFilterTypeOptionsException(
                'The MediaLanguageFilterType requires its options to be a comma-separated list of values'
            );
        }

        $values = \array_values(\array_filter(
            \explode(',', $options),
            fn (string $value) => '' !== $value
        ));

        $languages = \array_values(\array_filter($values, fn (string $value) => self::NONE_VALUE !== $value));
        $includeNone = \in_array(self::NONE_VALUE, $values, true);

        /** @var ExpressionInterface[] $expressions */
        $expressions = [];
        if ([] !== $languages) {
            $expressions[] = $listBuilder->createInExpression($fieldDescriptor, $languages);
        }
        if ($includeNone) {
            $expressions[] = $listBuilder->createIsNullExpression($fieldDescriptor);
        }

        if ([] === $expressions) {
            return;
        }

        $listBuilder->addExpression(
            1 === \count($expressions) ? $expressions[0] : $listBuilder->createOrExpression($expressions)
        );
    }

    public static function getDefaultIndexName(): string
    {
        return 'media_language';
    }
}
