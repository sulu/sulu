<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Types\Block;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Content\Compat\Block\BlockPropertyType;
use Sulu\Component\Content\Compat\Metadata;
use Sulu\Component\Content\Types\Block\ScheduleBlockSkipper;
use Symfony\Bridge\PhpUnit\ClockMock;

class ScheduleBlockSkipperTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        ClockMock::register(ScheduleBlockSkipper::class);
    }

    public function setUp(): void
    {
        $this->scheduleBlockSkipper = new ScheduleBlockSkipper();
    }

    public function testShouldNotSkipWithObjectAsSettings()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings(new \stdClass());

        $this->assertFalse($this->scheduleBlockSkipper->shouldSkip($blockPropertyType));
    }

    public function testShouldNotSkipWithEmptyArrayAsSettings()
    {
        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings([]);

        $this->assertFalse($this->scheduleBlockSkipper->shouldSkip($blockPropertyType));
    }

    public function provideShouldSkip()
    {
        return [
            [
                [
                    'schedules_enabled' => true,
                ],
                '2020-11-19T08:00:00',
                false,
            ],
            [
                [
                    'schedules' => [
                        ['type' => 'fixed', 'start' => '2020-11-15T08:00:00', 'end' => '2020-11-18T18:00'],
                    ],
                ],
                '2020-11-19T08:00:00',
                false,
            ],
            [
                [
                    'schedules_enabled' => false,
                    'schedules' => [
                        ['type' => 'fixed', 'start' => '2020-11-15T08:00:00', 'end' => '2020-11-18T18:00'],
                    ],
                ],
                '2020-11-19T08:00:00',
                false,
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => new \stdClass(),
                ],
                '2020-11-19T08:00:00',
                false,
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        ['type' => 'fixed', 'start' => '2020-11-15T08:00:00', 'end' => '2020-11-18T18:00'],
                    ],
                ],
                '2020-11-19T08:00:00',
                true,
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        ['type' => 'fixed', 'start' => '2020-11-15T08:00:00', 'end' => '2020-11-18T18:00'],
                    ],
                ],
                '2020-11-16T08:00:00',
                false,
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        ['type' => 'fixed', 'start' => '2020-11-15T08:00:00', 'end' => '2020-11-18T18:00'],
                        ['type' => 'fixed', 'start' => '2020-11-20T08:00:00', 'end' => '2020-11-25T18:00'],
                    ],
                ],
                '2020-11-19T08:00:00',
                true,
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        ['type' => 'fixed', 'start' => '2020-11-15T08:00:00', 'end' => '2020-11-19T18:00'],
                        ['type' => 'fixed', 'start' => '2020-11-20T08:00:00', 'end' => '2020-11-25T18:00'],
                    ],
                ],
                '2020-11-19T08:00:00',
                false,
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['monday', 'tuesday'],
                            'start' => '08:00:00',
                            'end' => '12:00:00',
                        ],
                    ],
                ],
                '2020-11-19T10:00:00', // Thursday
                true,
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '08:00:00',
                            'end' => '12:00:00',
                        ],
                    ],
                ],
                '2020-11-19T10:00:00', // Thursday
                false,
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '08:00:00',
                            'end' => '12:00:00',
                        ],
                    ],
                ],
                '2020-11-19T20:00:00', // Thursday
                true,
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '08:00:00',
                            'end' => '12:00:00',
                        ],
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '14:00:00',
                            'end' => '21:00:00',
                        ],
                    ],
                ],
                '2020-11-19T20:00:00', // Thursday
                false,
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '08:00:00',
                            'end' => '23:00:00',
                        ],
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '14:00:00',
                            'end' => '21:00:00',
                        ],
                    ],
                ],
                '2020-11-19T20:00:00', // Thursday
                false,
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '08:00:00',
                            'end' => '12:00:00',
                        ],
                        ['type' => 'fixed', 'start' => '2020-11-15T08:00:00', 'end' => '2020-11-18T18:00'],
                    ],
                ],
                '2020-11-19T20:00:00', // Thursday
                true,
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '08:00:00',
                            'end' => '20:00:00',
                        ],
                        ['type' => 'fixed', 'start' => '2020-11-15T08:00:00', 'end' => '2020-11-18T18:00'],
                    ],
                ],
                '2020-11-19T20:00:00', // Thursday
                false,
            ],
            [
                [
                    'schedules_enabled' => true,
                    'schedules' => [
                        [
                            'type' => 'weekly',
                            'days' => ['thursday'],
                            'start' => '08:00:00',
                            'end' => '12:00:00',
                        ],
                        ['type' => 'fixed', 'start' => '2020-11-19T08:00:00', 'end' => '2020-11-20T18:00'],
                    ],
                ],
                '2020-11-19T20:00:00', // Thursday
                false,
            ],
        ];
    }

    /**
     * @dataProvider provideShouldSkip
     */
    public function testShouldSkip($settings, $now, $skip)
    {
        $nowDateTime = new \DateTime($now);
        ClockMock::withClockMock($nowDateTime->getTimestamp());

        $blockPropertyType = new BlockPropertyType('type1', new Metadata([]));
        $blockPropertyType->setSettings($settings);

        $this->assertEquals($skip, $this->scheduleBlockSkipper->shouldSkip($blockPropertyType));
        ClockMock::withClockMock(false);
    }
}
