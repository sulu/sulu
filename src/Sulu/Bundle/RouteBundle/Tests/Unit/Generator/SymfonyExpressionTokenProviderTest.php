<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Unit\Generator;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\RouteBundle\Generator\CannotEvaluateTokenException;
use Sulu\Bundle\RouteBundle\Generator\SymfonyExpressionTokenProvider;
use Sulu\Bundle\RouteBundle\Model\RoutableInterface;
use Symfony\Component\Translation\Translator;

class SymfonyExpressionTokenProviderTest extends TestCase
{
    public function testResolve()
    {
        $translator = $this->prophesize(Translator::class);
        $translator->getLocale()->willReturn('de');
        $translator->setLocale('de')->shouldBeCalled();
        $entity = $this->prophesize(RoutableInterface::class);
        $entity->getLocale()->willReturn('en');

        $entity->getLocale = function() {
            return 'en';
        };
        $entity->name = 'TEST';
        $provider = new SymfonyExpressionTokenProvider($translator->reveal());
        $this->assertEquals('TEST', $provider->provide($entity, 'object.name'));
    }

    public function testResolveTranslation()
    {
        $translator = $this->prophesize(Translator::class);
        $translator->getLocale()->willReturn('de');
        $translator->setLocale('de')->shouldBeCalled();
        $translator->trans('test-key', Argument::cetera())->willReturn('TEST');
        $entity = $this->prophesize(RoutableInterface::class);
        $entity->getLocale()->willReturn('en');

        $entity->getLocale = function() {
            return 'en';
        };
        $provider = new SymfonyExpressionTokenProvider($translator->reveal());
        $this->assertEquals('TEST', $provider->provide($entity, 'translator.trans("test-key")'));
    }

    public function testResolveWithImplode()
    {
        $translator = $this->prophesize(Translator::class);
        $translator->getLocale()->willReturn('de');
        $translator->setLocale('de')->shouldBeCalled();

        $entity = ['title', 'subtitle'];

        $provider = new SymfonyExpressionTokenProvider($translator->reveal());

        $this->assertEquals(
            'events/title-subtitle',
            $provider->provide($entity, '"events/" ~ implode("-", object)')
        );
    }

    public function testResolveWithLocale(): void
    {
        $translator = $this->prophesize(Translator::class);
        $translator->getLocale()->willReturn('de');
        $translator->setLocale('de')->shouldBeCalled();
        $translator->setLocale('es')->shouldBeCalled();
        $translator->trans('test-key', Argument::cetera())->willReturn('TEST');

        $entity = [
            'title',
            'subtitle',
        ];

        $provider = new SymfonyExpressionTokenProvider($translator->reveal());

        $this->assertEquals(
            'TEST',
            $provider->provide($entity, 'translator.trans("test-key")', ['locale' => 'es'])
        );
    }

    public function testResolveWithIsArray()
    {
        $translator = $this->prophesize(Translator::class);
        $translator->getLocale()->willReturn('de');
        $translator->setLocale('de')->shouldBeCalled();

        $entity = new \stdClass();
        $array = ['first-array-value'];

        $provider = new SymfonyExpressionTokenProvider($translator->reveal());

        $this->assertEquals(
            'not-array',
            $provider->provide($entity, 'is_array(object) ? object[0] : "not-array"')
        );

        $this->assertEquals(
            'first-array-value',
            $provider->provide($array, 'is_array(object) ? object[0] : "not-array"')
        );
    }

    public function testResolveNotExists()
    {
        $this->expectException(CannotEvaluateTokenException::class);
        $translator = $this->prophesize(Translator::class);
        $translator->getLocale()->willReturn('en');
        $translator->setLocale('en')->shouldBeCalled();
        $entity = new \stdClass();
        $provider = new SymfonyExpressionTokenProvider($translator->reveal());
        $provider->provide($entity, 'object.title');
    }

    public function testResolveTranslationAddResource(): void
    {
        $this->expectException(CannotEvaluateTokenException::class);

        $translator = $this->prophesize(Translator::class);
        $translator->getLocale()->willReturn('de');
        $translator->setLocale('de')->shouldBeCalled();
        $entity = $this->prophesize(RoutableInterface::class);
        $entity->getLocale()->willReturn('en');

        $entity->getLocale = function() {
            return 'en';
        };
        $provider = new SymfonyExpressionTokenProvider($translator->reveal());
        $provider->provide($entity, 'translator.addResource("php", "/test.php")');
    }
}
