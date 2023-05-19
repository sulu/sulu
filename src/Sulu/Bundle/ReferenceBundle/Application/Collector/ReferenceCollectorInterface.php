<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Application\Collector;

use Doctrine\Common\Collections\ArrayCollection;
use Sulu\Bundle\ReferenceBundle\Domain\Model\ReferenceInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Component\Content\Document\WorkflowStage;

interface ReferenceCollectorInterface
{
    public function addReference(
        string $resourceKey,
        string $resourceId,
        string $title,
        string $property,
        ?string $securityContext = null,
        ?string $securityObjectType = null,
        ?string $securityObjectId = null
    ): ReferenceInterface;
}
