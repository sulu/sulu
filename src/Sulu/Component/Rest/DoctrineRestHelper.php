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

use Doctrine\Common\Collections\Collection;
use Sulu\Component\Rest\ListBuilder\ListRestHelper;

/**
 * Defines some common REST functionalities.
 */
class DoctrineRestHelper extends RestHelper implements RestHelperInterface
{
    public function __construct(ListRestHelper $listRestHelper)
    {
        parent::__construct($listRestHelper);
    }

    /**
     * {@inheritdoc}
     */
    public function processSubEntities(
        $entities,
        array $requestEntities,
        callable $get,
        callable $add = null,
        callable $update = null,
        callable $delete = null
    ) {
        /* @var Collection $entities */
        $success = parent::processSubEntities($entities, $requestEntities, $get, $add, $update, $delete);

        if (count($entities) > 0) {
            $newEntities = $entities->getValues();
            $entities->clear();
            foreach ($newEntities as $value) {
                $entities->add($value);
            }
        }

        return $success;
    }
}
