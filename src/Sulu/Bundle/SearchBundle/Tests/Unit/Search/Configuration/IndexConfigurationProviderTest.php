<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Search\Configuration;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Contracts\Translation\TranslatorInterface;

class IndexConfigurationProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<TranslatorInterface>
     */
    private $translator;

    public function setUp(): void
    {
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->translator->trans(Argument::cetera())->willReturnArgument(0);
    }

    public function testGetIndexConfigurations(): void
    {
        $indexConfigurationProvider = new IndexConfigurationProvider(
            $this->translator->reveal(),
            [
                'index1' => [
                    'name' => 'test.index',
                    'icon' => 'su-test',
                    'view' => [
                        'name' => 'test1',
                        'result_to_view' => ['webspace_key' => 'webspace'],
                    ],
                    'security_context' => 'sulu.security.index1',
                    'contexts' => ['website'],
                ],
                'index2' => [
                    'name' => 'test.index2',
                    'icon' => 'su-icon',
                    'view' => [
                        'name' => 'test2',
                        'result_to_view' => [],
                    ],
                    'security_context' => 'sulu.security.index2',
                ],
            ]
        );

        $indexConfigurations = $indexConfigurationProvider->getIndexConfigurations();

        $this->assertEquals('index1', $indexConfigurations['index1']->getIndexName());
        $this->assertEquals('su-test', $indexConfigurations['index1']->getIcon());
        $this->assertEquals('test.index', $indexConfigurations['index1']->getName());
        $this->assertEquals('sulu.security.index1', $indexConfigurations['index1']->getSecurityContext());
        $this->assertEquals(['website'], $indexConfigurations['index1']->getContexts());
        $this->assertEquals(
            new Route('test1', ['webspace_key' => 'webspace']),
            $indexConfigurations['index1']->getRoute()
        );
        $this->assertEquals('index2', $indexConfigurations['index2']->getIndexName());
        $this->assertEquals('su-icon', $indexConfigurations['index2']->getIcon());
        $this->assertEquals('sulu.security.index2', $indexConfigurations['index2']->getSecurityContext());
        $this->assertEquals(new Route('test2', []), $indexConfigurations['index2']->getRoute());
    }

    public function testGetIndexConfiguration(): void
    {
        $indexConfigurationProvider = new IndexConfigurationProvider(
            $this->translator->reveal(),
            [
                'index1' => [
                    'name' => 'index1',
                    'icon' => 'su-test',
                    'view' => [
                        'name' => 'test1',
                        'result_to_view' => ['webspace_key' => 'webspace'],
                    ],
                    'security_context' => 'sulu.security.index1',
                    'contexts' => [],
                ],
                'index2' => [
                    'name' => 'index2',
                    'icon' => 'su-icon',
                    'view' => [
                        'name' => 'test2',
                        'result_to_view' => [],
                    ],
                    'security_context' => 'sulu.security.index2',
                    'contexts' => [],
                ],
            ]
        );

        $indexConfiguration = $indexConfigurationProvider->getIndexConfiguration('index2');

        $this->assertEquals('index2', $indexConfiguration->getIndexName());
        $this->assertEquals('su-icon', $indexConfiguration->getIcon());
        $this->assertEquals(new Route('test2', []), $indexConfiguration->getRoute());
        $this->assertEquals('sulu.security.index2', $indexConfiguration->getSecurityContext());
    }
}
