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
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationRegistry;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationRegistryInterface;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;
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
        return array(
            array(
                array(
                    'alias1' => array('service1', 'service2'),
                    'alias2' => array('service3'),
                )
            )
        );
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
        return array(
            array(
                array(
                    'alias1' => array('service1', 'service2'),
                    'alias2' => array('service3'),
                ),
                array(
                    'option1' => 'value1',
                ),
                array(
                    'service1' => array('tab1', 'tab2'),
                    'service2' => array('tab3'),
                    'service3' => array('tab4'),
                ),
                array(
                    'alias1' => array('tab1', 'tab2', 'tab3'),
                    'alias2' => array('tab4'),
                )
            )
        );
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

        foreach ($contentNavigationData as $service => $items) {
            $contentNavigationProvider = $this->prophesize(ContentNavigationProviderInterface::class);
            $contentNavigationProvider->getNavigationItems($options)->willReturn($items);
            $this->container->get($service)->willReturn($contentNavigationProvider);
        }

        foreach ($results as $alias => $result) {
            $this->assertEquals($result, $this->contentNavigationCollector->getNavigationItems($alias, $options));
        }
    }

    public function testGetNavigationItemsWithNotExistentAlias()
    {
        $this->setExpectedException(ContentNavigationAliasNotFoundException::class);

        $this->contentNavigationCollector->getNavigationItems('not_existent_alias');
    }
}
