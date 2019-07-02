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
use Symfony\Component\Translation\TranslatorInterface;

class IndexConfigurationProviderTest extends TestCase
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function setUp()
    {
        $this->translator = $this->prophesize(TranslatorInterface::class);
    }

    public function testGetIndexConfigurations()
    {
        $this->translator->trans('test.index', [], 'admin')->willReturn('index 1');

        $indexConfigurationProvider = new IndexConfigurationProvider(
            $this->translator->reveal(),
            [
                'index1' => [
                    'name' => 'test.index',
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
            $this->translator->reveal(),
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
