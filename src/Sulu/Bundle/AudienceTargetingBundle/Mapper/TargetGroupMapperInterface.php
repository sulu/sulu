<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Mapper;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;

/**
 * Interface for target group mapper.
 */
interface TargetGroupMapperInterface
{
    /**
     * Assigns given data array to TargetGroup entity.
     *
     * @param TargetGroupInterface $targetGroup
     * @param array $data
     * @param bool $doPatch
     */
    public function mapDataToTargetGroup(TargetGroupInterface $targetGroup, array $data, $doPatch = true);
}
