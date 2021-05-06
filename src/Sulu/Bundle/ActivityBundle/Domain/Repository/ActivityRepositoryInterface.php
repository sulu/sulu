<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Domain\Repository;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;

interface ActivityRepositoryInterface
{
    public function createForDomainEvent(DomainEvent $domainEvent): ActivityInterface;

    public function addAndCommit(ActivityInterface $activity): void;
}
