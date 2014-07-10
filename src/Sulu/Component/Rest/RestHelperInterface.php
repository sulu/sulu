<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest;

use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor\AbstractFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;

interface RestHelperInterface
{
    /**
     * Initializes the given ListBuilder with the standard values from the request
     * @param ListBuilderInterface $listBuilder The ListBuilder to initialize
     * @param AbstractFieldDescriptor[] $fieldDescriptors The FieldDescriptors available for this object type
     */
    public function initializeListBuilder(ListBuilderInterface $listBuilder, array $fieldDescriptors);

    /**
     * This method processes a put request (delete non-existing entities, update existing entities, add new
     * entries), and let the single actions be modified by callbacks
     * @param ApiEntity[] $entities
     * @param $requestEntities
     * @param callback $deleteCallback
     * @param callback $updateCallback
     * @param callback $addCallback
     * @return bool
     * @deprecated
     */
    public function processSubEntities($entities, $requestEntities, $deleteCallback, $updateCallback, $addCallback);
} 
