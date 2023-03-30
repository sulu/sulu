<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TrashBundle\Tests\Application\Entity;

use JMS\Serializer\Annotation\Groups;

class TestResource
{
    /**
     * @Groups({"restoreSerializationGroup"})
     */
    private string $property1 = 'value-1';

    /**
     * @Groups({"otherSerializationGroup"})
     */
    private string $property2 = 'value-2';
}
