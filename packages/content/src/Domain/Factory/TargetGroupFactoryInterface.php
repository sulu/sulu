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

namespace Sulu\Content\Domain\Factory;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroup;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

interface TargetGroupFactoryInterface
{
    /**
     * @param int[] $targetGroupIds
     *
     * @return TargetGroupInterface[]
     */
    public function create(array $targetGroupIds): array;
}
