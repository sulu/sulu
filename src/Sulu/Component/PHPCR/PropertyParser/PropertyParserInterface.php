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

interface PropertyParserInterface
{
    /**
     * Parses the list of propertyName => propertyValue from the PHPCR\NodeInterface::getPropertiesValues into a tree like
     * structure to make it easier to work with.
     */
    public function parse(array $array): array;

    /**
     * Returns a list of property names under the current property.
     */
    public function keyIterator(array $node): \Generator;
}
