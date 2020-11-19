<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\Block;

use Sulu\Component\Content\Compat\Block\BlockPropertyType;

class ScheduleBlockSkipper implements BlockSkipperInterface
{
    public function shouldSkip(BlockPropertyType $block): bool
    {
        $blockPropertyTypeSettings = $block->getSettings();

        if (!\is_array($blockPropertyTypeSettings)
            || !isset($blockPropertyTypeSettings['schedules_enabled'])
            || !$blockPropertyTypeSettings['schedules_enabled']
            || !isset($blockPropertyTypeSettings['schedules'])
            || !\is_array($blockPropertyTypeSettings['schedules'])
        ) {
            return false;
        }

        $now = \DateTime::createFromFormat('U', time());

        foreach ($blockPropertyTypeSettings['schedules'] as $schedule) {
            switch ($schedule['type']) {
                case 'fixed':
                    $start = new \DateTime($schedule['start']);
                    $end = new \DateTime($schedule['end']);
                    if ($now >= $start && $now <= $end) {
                        return false;
                    }
                    break;
                case 'weekly':
                    $weekday = \strtolower($now->format('l'));
                    if (!\is_array($schedule['days']) || !\in_array($weekday, $schedule['days'])) {
                        break;
                    }

                    $start = new \DateTime($schedule['start']);
                    $end = new \DateTime($schedule['end']);
                    if ($now >= $start && $now <= $end) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }
}
