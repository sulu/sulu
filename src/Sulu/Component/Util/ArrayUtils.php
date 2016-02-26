<?php
/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Util;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Collection utilities working with symfony-expressions.
 */
final class ArrayUtils
{
    /**
     * Filter array with given symfony-expression
     *
     * @param array $collection
     * @param string $expression
     * @param array $context
     *
     * @return array
     */
    public static function filter(array $collection, $expression, array $context = [])
    {
        $language = new ExpressionLanguage();

        return array_filter(
            $collection,
            function ($item, $key) use ($language, $expression, $context) {
                return $language->evaluate($expression, array_merge($context, ['item' => $item, 'key' => $key]));
            },
            ARRAY_FILTER_USE_BOTH
        );
    }
}
