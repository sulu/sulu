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
     * @param array<Property> $shadowedKeys
     *                                      Shadowed keys are keys that shouldn't have a value or shouldn't have children. In the current representation
     *                                      a property has to be a leaf node to have a value. Here is an example:
     *                                      'some_prop' => 10,
     *                                      'some_prop-other-prop' => 'hello'
     *                                      But then the property `some_prop` has the value 10 and at the same time it also has a sub property of `other_prop`.
     *                                      In this case we would mark the `some_prop` node as shadowed.
     *
     *      This doesn't mean that Sulu doesn't use that property anymore
     */
    public function __construct(
        public array $shadowedKeys = []
    ) {
    }

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
        $this->shadowedKeys = [];
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
                        $this->shadowedKeys[] = $current;
                        $current = [];
                    }
                }

                // Handling properties like a-b becomes $current[a][b]
                if (!\array_key_exists($part, $current)) {
                    $current[$part] = [];
                }
                $current = &$current[$part];
                if ($current instanceof Property) {
                    $this->shadowedKeys[] = $current;
                    $current = [];
                }
            }

            // If the current node is not empty it has children and all the children are shadowed by this key
            if (0 !== \count($current)) {
                $this->shadowedKeys[] = new Property($key, $value);
                continue;
            }

            $current = new Property($key, $value);
        }

        return $result;
    }

    /**
     * Returns a list of property names under the current property.
     *
     * @param array<mixed> $node
     *
     * @return \Generator<string>
     */
    public function keyIterator(array $node): \Generator
    {
        foreach ($node as $key => $value) {
            if (\is_array($value)) {
                yield from $this->keyIterator($value);
            } else {
                yield $value->getPath();
            }
        }
    }
}
