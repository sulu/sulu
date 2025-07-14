<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Application\Visitor;

interface SmartContentFiltersVisitorInterface
{
    /**
     * This method is used to visit the data and apply any necessary filters to it, returning the modified data structure.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $filters
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    public function visit(array $data, array $filters, array $parameters): array;
}
