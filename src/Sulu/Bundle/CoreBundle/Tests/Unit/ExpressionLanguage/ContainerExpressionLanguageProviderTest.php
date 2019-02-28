<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Tests\Unit\ExpressionLanguage;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\CoreBundle\ExpressionLanguage\ContainerExpressionLanguageProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ContainerExpressionLanguageProviderTest extends TestCase
{
    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $containerExpressionLanguageProvider = new ContainerExpressionLanguageProvider($this->container->reveal());
        $this->expressionLanguage = new ExpressionLanguage(null, [$containerExpressionLanguageProvider]);
    }

    public function testEvaluateWithService()
    {
        $testService = new \stdClass();
        $testService->variable = 'test';

        $this->container->get('test_service')->willReturn($testService);
        $this->assertEquals('test', $this->expressionLanguage->evaluate('service("test_service").variable'));
    }

    public function testEvaluateWithParameter()
    {
        $this->container->getParameter('test_variable')->willReturn('test');
        $this->assertEquals('test', $this->expressionLanguage->evaluate('parameter("test_variable")'));
    }
}
