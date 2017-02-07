<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest;

use Sulu\Component\Rest\ListBuilder\FieldDescriptor;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;
use Traversable;

interface RestHelperInterface
{
    /**
     * Initializes the given ListBuilder with the standard values from the request.
     *
     * @param ListBuilderInterface      $listBuilder      The ListBuilder to initialize
     * @param FieldDescriptor[] $fieldDescriptors The FieldDescriptors available for this object type
     */
    public function initializeListBuilder(ListBuilderInterface $listBuilder, array $fieldDescriptors);

    /**
     * This method processes a put request (delete non-existing entities, update existing entities, add new
     * entries), and let the single actions be modified by callbacks.
     *
     * @param Traversable $entities        The list of entities to work on
     * @param array       $requestEntities The entities as retrieved from the request
     * @param callable    $get             The
     * @param callable    $add
     * @param callable    $update
     * @param callable    $delete
     *
     * @return bool
     */
    public function processSubEntities(
        $entities,
        array $requestEntities,
        callable $get,
        callable $add = null,
        callable $update = null,
        callable $delete = null
    );
}
