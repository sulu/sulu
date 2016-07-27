<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Search\Configuration;

class IndexConfigurationProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetIndexConfigurations()
    {
        $indexConfigurationProvider = new IndexConfigurationProvider(
            [
                'index1' => [
                    'name' => 'index 1',
                    'security_context' => 'sulu.security.index1',
                    'contexts' => ['website'],
                ],
                'index2' => [
                    'security_context' => 'sulu.security.index2',
                ],
            ]
        );

        $indexConfigurations = $indexConfigurationProvider->getIndexConfigurations();

        $this->assertEquals('index1', $indexConfigurations['index1']->getIndexName());
        $this->assertEquals('index 1', $indexConfigurations['index1']->getName());
        $this->assertEquals('sulu.security.index1', $indexConfigurations['index1']->getSecurityContext());
        $this->assertEquals(['website'], $indexConfigurations['index1']->getContexts());
        $this->assertEquals('index2', $indexConfigurations['index2']->getIndexName());
        $this->assertEquals('sulu.security.index2', $indexConfigurations['index2']->getSecurityContext());
    }

    public function testGetIndexConfiguration()
    {
        $indexConfigurationProvider = new IndexConfigurationProvider(
            [
                'index1' => [
                    'security_context' => 'sulu.security.index1',
                ],
                'index2' => [
                    'security_context' => 'sulu.security.index2',
                ],
            ]
        );

        $indexConfiguration = $indexConfigurationProvider->getIndexConfiguration('index2');

        $this->assertEquals('index2', $indexConfiguration->getIndexName());
        $this->assertEquals('sulu.security.index2', $indexConfiguration->getSecurityContext());
    }
}
