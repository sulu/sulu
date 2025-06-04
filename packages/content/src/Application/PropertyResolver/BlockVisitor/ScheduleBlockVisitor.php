<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Application\PropertyResolver\BlockVisitor;

use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeRequestStore;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

class ScheduleBlockVisitor implements BlockVisitorInterface
{
    public function __construct(
        private RequestAnalyzerInterface $requestAnalyzer,
        private CacheLifetimeRequestStore $cacheLifetimeRequestStore,
    ) {
    }

    public function visit(array $block): ?array
    {
        $blockPropertyTypeSettings = $block['settings'] ?? [];

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

        /** @var array<string, mixed> $schedule */
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
                    $year = (int) $now->format('Y');
                    $month = (int) $now->format('m');
                    $day = (int) $now->format('d');

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
                    // This loop checks the coming 7 days for matches of the weekdays selected by the user. This is
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

    /** @param array<string, mixed> $schedule */
    private function matchWeekday(\DateTime $datetime, array $schedule): bool
    {
        if (!\is_array($schedule['days'])) {
            return false;
        }

        return \in_array(\strtolower($datetime->format('l')), $schedule['days']);
    }
}
