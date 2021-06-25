<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TrashBundle\Application\TrashManager;

use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;

interface TrashManagerInterface
{
    public function store(string $resourceKey, object $object): TrashItemInterface;

    public function restore(TrashItemInterface $trashItem): object;

    public function remove(TrashItemInterface $trashItem): void;
}
