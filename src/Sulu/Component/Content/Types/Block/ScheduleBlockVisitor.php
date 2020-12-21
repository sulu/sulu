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

use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeRequestStore;
use Sulu\Component\Content\Compat\Block\BlockPropertyType;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

class ScheduleBlockVisitor implements BlockVisitorInterface
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var CacheLifetimeRequestStore
     */
    private $cacheLifetimeRequestStore;

    public function __construct(
        RequestAnalyzerInterface $requestAnalyzer,
        CacheLifetimeRequestStore $cacheLifetimeRequestStore
    ) {
        $this->requestAnalyzer = $requestAnalyzer;
        $this->cacheLifetimeRequestStore = $cacheLifetimeRequestStore;
    }

    public function visit(BlockPropertyType $block): ?BlockPropertyType
    {
        $blockPropertyTypeSettings = $block->getSettings();

        if (!\is_array($blockPropertyTypeSettings)
            || !isset($blockPropertyTypeSettings['schedules_enabled'])
            || !$blockPropertyTypeSettings['schedules_enabled']
            || !isset($blockPropertyTypeSettings['schedules'])
            || !\is_array($blockPropertyTypeSettings['schedules'])
        ) {
            return $block;
        }

        $now = $this->requestAnalyzer->getDateTime();
        $nowTimestamp = $now->getTimestamp();

        $returnBlock = false;

        foreach ($blockPropertyTypeSettings['schedules'] as $schedule) {
            switch ($schedule['type']) {
                case 'fixed':
                    $start = isset($schedule['start']) ? new \DateTime($schedule['start']) : null;
                    $end = isset($schedule['end']) ? new \DateTime($schedule['end']) : null;

                    if (!$start && !$end) {
                        $returnBlock = true;
                        continue 2;
                    }

                    if ($start) {
                        $this->cacheLifetimeRequestStore->setCacheLifetime($start->getTimestamp() - $nowTimestamp);
                    }

                    if ($end) {
                        $this->cacheLifetimeRequestStore->setCacheLifetime($end->getTimestamp() - $nowTimestamp);
                    }

                    if (!$start && $now <= $end) {
                        $returnBlock = true;
                        continue 2;
                    }

                    if (!$end && $now >= $start) {
                        $returnBlock = true;
                        continue 2;
                    }

                    if ($now >= $start && $now <= $end) {
                        $returnBlock = true;
                        continue 2;
                    }
                    break;
                case 'weekly':
                    $year = $now->format('Y');
                    $month = $now->format('m');
                    $day = $now->format('d');

                    $start = new \DateTime($schedule['start']);
                    $start->setDate($year, $month, $day);
                    $end = new \DateTime($schedule['end']);
                    $end->setDate($year, $month, $day);

                    if ($end < $start) {
                        // If the end date is smaller than the start date, it means that the user has entered a time
                        // combination that spans multiple days. In order to make sure that the start date is before the
                        // end date the start date has to be set to yesterday.
                        $start->modify('-1 day');
                    }

                    $i = 0;
                    // This loop checks for the coming 7 days for matches of the weekdays selected by the users. This is
                    // necessary to find the exact weekday on which the next change happens, so that it can be set as
                    // cachelifetime.
                    do {
                        if ($this->matchWeekday($start, $schedule)) {
                            break;
                        }

                        $start->modify('+1 day');
                        $end->modify('+1 day');
                        ++$i;
                    } while ($i < 7);

                    $this->cacheLifetimeRequestStore->setCacheLifetime($start->getTimestamp() - $nowTimestamp);
                    $this->cacheLifetimeRequestStore->setCacheLifetime($end->getTimestamp() - $nowTimestamp);

                    if ($now >= $start && $now <= $end) {
                        $returnBlock = true;
                    }
                    break;
            }
        }

        return $returnBlock ? $block : null;
    }

    private function matchWeekday(\DateTime $datetime, $schedule)
    {
        if (!\is_array($schedule['days'])) {
            // If the user has not selected any weekdays, then the given times are valid for every weekday.
            return true;
        }

        return \in_array(\strtolower($datetime->format('l')), $schedule['days']);
    }
}
