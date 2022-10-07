<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\SmartContent\DataProviderInterface;
use Sulu\Component\SmartContent\DataProviderPool;
use Sulu\Component\SmartContent\Exception\DataProviderAliasAlreadyExistsException;
use Sulu\Component\SmartContent\Exception\DataProviderNotExistsException;

class DataProviderPoolTest extends TestCase
{
    use ProphecyTrait;

    public function addProvider()
    {
        $pool1 = new DataProviderPool(true);
        $pool2 = new DataProviderPool(true);

        $provider1 = $this->prophesize(DataProviderInterface::class);
        $provider2 = $this->prophesize(DataProviderInterface::class);
        $provider3 = $this->prophesize(DataProviderInterface::class);

        $pool2->add('test-1', $provider1->reveal());

        return [
            [
                $pool1,
                [
                    ['alias' => 'test', 'provider' => $provider1->reveal()],
                ],
                [
                    'test' => $provider1->reveal(),
                ],
            ],
            [
                $pool1,
                [
                    ['alias' => 'test', 'provider' => $provider1->reveal()],
                    ['alias' => 'test', 'provider' => $provider2->reveal()],
                ],
                [
                    'test' => $provider1->reveal(),
                ],
                DataProviderAliasAlreadyExistsException::class,
            ],
            [
                $pool2,
                [
                    ['alias' => 'test-2', 'provider' => $provider2->reveal()],
                    ['alias' => 'test-3', 'provider' => $provider3->reveal()],
                ],
                [
                    'test-1' => $provider1->reveal(),
                    'test-2' => $provider2->reveal(),
                    'test-3' => $provider3->reveal(),
                ],
            ],
        ];
    }

    /**
     * @dataProvider addProvider
     */
    public function testAdd(DataProviderPool $pool, $providers, $expectedProviders, $exceptionName = null): void
    {
        if ($exceptionName) {
            $this->expectException($exceptionName);
        }

        foreach ($providers as $item) {
            $pool->add($item['alias'], $item['provider']);
        }

        $this->assertEquals($expectedProviders, $pool->getAll());
    }

    public function existsProvider()
    {
        $pool = new DataProviderPool(true);
        $provider = $this->prophesize(DataProviderInterface::class);
        $pool->add('test', $provider->reveal());

        return [
            [
                $pool,
                'test',
                true,
            ],
            [
                $pool,
                'test-1',
                false,
            ],
        ];
    }

    /**
     * @dataProvider existsProvider
     */
    public function testExists(DataProviderPool $pool, $alias, $expected): void
    {
        $this->assertEquals($expected, $pool->exists($alias));
    }

    public function getProvider()
    {
        $pool = new DataProviderPool(true);
        $provider = $this->prophesize(DataProviderInterface::class);
        $pool->add('test', $provider->reveal());

        return [
            [
                $pool,
                'test',
                $provider->reveal(),
            ],
            [
                $pool,
                'test-1',
                null,
                DataProviderNotExistsException::class,
            ],
        ];
    }

    /**
     * @dataProvider getProvider
     */
    public function testGet(DataProviderPool $pool, $alias, $expectedProvider, $exceptionName = null): void
    {
        if ($exceptionName) {
            $this->expectException($exceptionName);
        }

        $this->assertEquals($expectedProvider, $pool->get($alias));
    }

    public function getAllProvider()
    {
        $pool1 = new DataProviderPool(true);
        $pool2 = new DataProviderPool(true);
        $provider1 = $this->prophesize(DataProviderInterface::class);
        $provider2 = $this->prophesize(DataProviderInterface::class);
        $pool1->add('test-1', $provider1->reveal());
        $pool1->add('test-2', $provider2->reveal());

        return [
            [
                $pool1,
                [
                    'test-1' => $provider1->reveal(),
                    'test-2' => $provider2->reveal(),
                ],
            ],
            [
                $pool2,
                [],
            ],
        ];
    }

    /**
     * @dataProvider getAllProvider
     */
    public function testGetAll(DataProviderPool $pool, $expectedProviders): void
    {
        $this->assertEquals($expectedProviders, $pool->getAll());
    }
}
