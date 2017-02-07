<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Unit\Generator;

use Sulu\Bundle\RouteBundle\Generator\CannotEvaluateTokenException;
use Sulu\Bundle\RouteBundle\Generator\SymfonyExpressionTokenProvider;
use Symfony\Component\Translation\TranslatorInterface;

class SymfonyExpressionTokenProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testResolve()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $entity = new \stdClass();
        $entity->name = 'TEST';

        $provider = new SymfonyExpressionTokenProvider($translator->reveal());
        $this->assertEquals('TEST', $provider->provide($entity, 'object.name'));
    }

    public function testResolveTranslation()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $translator->trans('test-key')->willReturn('TEST');

        $entity = new \stdClass();

        $provider = new SymfonyExpressionTokenProvider($translator->reveal());
        $this->assertEquals('TEST', $provider->provide($entity, 'translator.trans("test-key")'));
    }

    public function testResolveNotExists()
    {
        $this->setExpectedException(CannotEvaluateTokenException::class);

        $translator = $this->prophesize(TranslatorInterface::class);
        $entity = new \stdClass();

        $provider = new SymfonyExpressionTokenProvider($translator->reveal());
        $provider->provide($entity, 'object.title');
    }
}
