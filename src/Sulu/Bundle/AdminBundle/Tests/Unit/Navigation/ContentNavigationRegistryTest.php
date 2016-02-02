<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Navigation;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationAliasNotFoundException;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationRegistry;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationRegistryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContentNavigationRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentNavigationRegistryInterface
     */
    private $contentNavigationCollector;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->contentNavigationCollector = new ContentNavigationRegistry($this->container->reveal());
    }

    public function provideContentNavigationMappings()
    {
        return [
            [
                [
                    'alias1' => ['service1', 'service2'],
                    'alias2' => ['service3'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideContentNavigationMappings
     */
    public function testAddContentNavigationProvider($mappings)
    {
        foreach ($mappings as $alias => $services) {
            foreach ($services as $service) {
                $this->contentNavigationCollector->addContentNavigationProvider($alias, $service);
            }
        }

        $this->assertAttributeEquals($mappings, 'providers', $this->contentNavigationCollector);
    }

    public function provideContentNavigationItems()
    {
        return [
            [
                [
                    'alias1' => ['service1', 'service2'],
                    'alias2' => ['service3'],
                ],
                [
                    'option1' => 'value1',
                ],
                [
                    'service1' => [['tab1'], ['tab2']],
                    'service2' => [['tab3']],
                    'service3' => [['tab4']],
                ],
                [
                    'alias1' => [['tab1'], ['tab2'], ['tab3']],
                    'alias2' => [['tab4']],
                ],
            ],
            [
                [
                    'alias1' => ['service1', 'service2'],
                    'alias2' => ['service2', 'service3'],
                ],
                [],
                [
                    'service1' => [['tab1', 10], ['tab2', 20]],
                    'service2' => [['tab3', 15]],
                    'service3' => [['tab4', 10]],
                ],
                [
                    'alias1' => [['tab1'], ['tab3'], ['tab2']],
                    'alias2' => [['tab4'], ['tab3']],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideContentNavigationItems
     */
    public function testGetNavigationItems($mappings, $options, $contentNavigationData, $results)
    {
        foreach ($mappings as $alias => $services) {
            foreach ($services as $service) {
                $this->contentNavigationCollector->addContentNavigationProvider($alias, $service);
            }
        }

        $pos = 1;
        foreach ($contentNavigationData as $service => $items) {
            $contentNavigationProvider = $this->prophesize(ContentNavigationProviderInterface::class);

            $items = array_map(
                function ($item) use (&$pos) {
                    $navigationItem = new ContentNavigationItem($item[0]);
                    $navigationItem->setAction($item[0]);
                    $navigationItem->setPosition(isset($item[1]) ? $item[1] : $pos);

                    ++$pos;

                    return $navigationItem;
                },
                $items
            );

            $contentNavigationProvider->getNavigationItems($options)->willReturn($items);
            $this->container->get($service)->willReturn($contentNavigationProvider);
        }

        foreach ($results as $alias => $result) {
            $actual = $this->contentNavigationCollector->getNavigationItems($alias, $options);

            $this->assertEquals(
                $result,
                array_map(
                    function (ContentNavigationItem $item) {
                        return [$item->getAction()];
                    },
                    $actual
                )
            );
        }
    }

    public function testGetNavigationItemsWithNotExistentAlias()
    {
        $this->setExpectedException(ContentNavigationAliasNotFoundException::class);

        $this->contentNavigationCollector->getNavigationItems('not_existent_alias');
    }
}
