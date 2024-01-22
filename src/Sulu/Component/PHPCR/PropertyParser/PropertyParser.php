<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR\PropertyParser;

final class PropertyParser implements PropertyParserInterface
{
    /**
     * Parses the list of propertyName => propertyValue from the PHPCR\NodeInterface::getPropertiesValues into a tree like
     * structure to make it easier to work with.
     *
     * @param array<mixed> $array
     *
     * @return array<mixed>
     */
    public function parse(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            // Starting from the root node every time
            /** @var array<Property> $current */
            $current = &$result;
            foreach (\explode('-', $key) as $part) {
                // If the key contains a hash convert it to an array index
                // eg. 'hallo#1' is the same as $current[1][hallo]
                if (\str_contains($part, '#')) {
                    [$part, $index] = \explode('#', $part);
                    if (!\array_key_exists($index, $current)) {
                        $current[$index] = [];
                    }
                    $current = &$current[$index];
                    if ($current instanceof Property) {
                        $current = [];
                    }
                }

                // Handling properties like a-b becomes $current[a][b]
                if (!\array_key_exists($part, $current)) {
                    $current[$part] = [];
                }
                $current = &$current[$part];
                if ($current instanceof Property) {
                    $current = [];
                }
            }

            $current = new Property($key, $value);
        }

        return $result;
    }

    /**
     * Returns a list of property names under the current property.
     *
     * @param array<mixed> $properties
     *
     * @return \Generator<string>
     */
    public function keyIterator(array $properties): \Generator
    {
        foreach ($properties as $key => $value) {
            if (\is_array($value)) {
                yield from $this->keyIterator($value);
            } elseif ($value instanceof Property) {
                yield $value->getPath();
            }
        }
    }
}
