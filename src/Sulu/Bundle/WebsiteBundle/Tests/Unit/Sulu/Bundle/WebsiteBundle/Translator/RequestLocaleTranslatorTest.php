<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Sulu\Bundle\WebsiteBundle\Translator;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\WebsiteBundle\Translator\RequestLocaleTranslator;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class RequestLocaleTranslatorTest extends TestCase
{
    protected function mockRequest(?string $locale): Request
    {
        $request = $this->prophesize(Request::class);
        $attributes = $this->prophesize(ParameterBag::class);
        $request->attributes = $attributes->reveal();

        $requestAttributes = $this->prophesize(RequestAttributes::class);
        $attributes->has('_sulu')->willReturn(true);
        $attributes->get('_sulu')->willReturn($requestAttributes->reveal());

        $localization = null;
        if ($locale) {
            $localization = $this->prophesize(Localization::class);
        }

        $requestAttributes->getAttribute('localization')->willReturn($localization->reveal());
        $localization->getLocale(Localization::LCID)->willReturn($locale);

        return $request->reveal();
    }

    public function testGetLocale()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $requestAnalyzerTranslator = new RequestLocaleTranslator($translator->reveal(), $requestStack->reveal());

        $requestStack->getCurrentRequest()->willReturn($this->mockRequest('de'));
        $translator->setLocale('de')->shouldBeCalledTimes(1);
        $translator->getLocale()->willReturn('de');

        $this->assertEquals('de', $requestAnalyzerTranslator->getLocale());
    }

    public function testGetLocaleWithCountry()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $requestAnalyzerTranslator = new RequestLocaleTranslator($translator->reveal(), $requestStack->reveal());

        $requestStack->getCurrentRequest()->willReturn($this->mockRequest('de_AT'));
        $translator->setLocale('de_AT')->shouldBeCalledTimes(1);
        $translator->getLocale()->willReturn('de_AT');

        $this->assertEquals('de_AT', $requestAnalyzerTranslator->getLocale());
    }

    public function testGetLocaleTwice()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $requestAnalyzerTranslator = new RequestLocaleTranslator($translator->reveal(), $requestStack->reveal());

        $requestStack->getCurrentRequest()->willReturn($this->mockRequest('de'));
        $translator->setLocale('de')->shouldBeCalledTimes(1);
        $translator->getLocale()->willReturn('de');

        $this->assertEquals('de', $requestAnalyzerTranslator->getLocale());
        $this->assertEquals('de', $requestAnalyzerTranslator->getLocale());
    }

    public function testSetLocale()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $requestAnalyzerTranslator = new RequestLocaleTranslator($translator->reveal(), $requestStack->reveal());

        $requestStack->getCurrentRequest()->willReturn($this->mockRequest('de'));
        $translator->setLocale('de')->shouldNotBeCalled();
        $translator->setLocale('en')->shouldBeCalled();

        $requestAnalyzerTranslator->setLocale('en');
    }

    public function testSetLocaleTwice()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $requestAnalyzerTranslator = new RequestLocaleTranslator($translator->reveal(), $requestStack->reveal());

        $requestStack->getCurrentRequest()->willReturn($this->mockRequest('de'));
        $translator->setLocale('de')->shouldNotBeCalled();
        $translator->setLocale('en')->shouldBeCalled();

        $requestAnalyzerTranslator->setLocale('en');
        $requestAnalyzerTranslator->setLocale('en');
    }

    public function testTrans()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $requestAnalyzerTranslator = new RequestLocaleTranslator($translator->reveal(), $requestStack->reveal());

        $requestStack->getCurrentRequest()->willReturn($this->mockRequest('de'));
        $translator->setLocale('de')->shouldBeCalledTimes(1);
        $translator->trans('folder', ['test-1'], 'messages', 'de')->willReturn('Ordner');

        $this->assertEquals('Ordner', $requestAnalyzerTranslator->trans('folder', ['test-1'], 'messages', 'de'));
    }

    public function testTransTwice()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $requestAnalyzerTranslator = new RequestLocaleTranslator($translator->reveal(), $requestStack->reveal());

        $requestStack->getCurrentRequest()->willReturn($this->mockRequest('de'));
        $translator->setLocale('de')->shouldBeCalledTimes(1);
        $translator->trans('folder', ['test-1'], 'messages', 'de')->willReturn('Ordner');

        $this->assertEquals('Ordner', $requestAnalyzerTranslator->trans('folder', ['test-1'], 'messages', 'de'));
        $this->assertEquals('Ordner', $requestAnalyzerTranslator->trans('folder', ['test-1'], 'messages', 'de'));
    }

    public function testTransChoice()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $requestAnalyzerTranslator = new RequestLocaleTranslator($translator->reveal(), $requestStack->reveal());

        $requestStack->getCurrentRequest()->willReturn($this->mockRequest('de'));
        $translator->setLocale('de')->shouldBeCalledTimes(1);
        $translator->transChoice('folder', 2, ['test-1'], 'messages', 'de')->willReturn('Ordner');

        $this->assertEquals(
            'Ordner',
            $requestAnalyzerTranslator->transChoice('folder', 2, ['test-1'], 'messages', 'de')
        );
    }

    public function testTransChoiceTwice()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $requestAnalyzerTranslator = new RequestLocaleTranslator($translator->reveal(), $requestStack->reveal());

        $requestStack->getCurrentRequest()->willReturn($this->mockRequest('de'));
        $translator->setLocale('de')->shouldBeCalledTimes(1);
        $translator->transChoice('folder', 2, ['test-1'], 'messages', 'de')->willReturn('Ordner');

        $this->assertEquals(
            'Ordner',
            $requestAnalyzerTranslator->transChoice('folder', 2, ['test-1'], 'messages', 'de')
        );
        $this->assertEquals(
            'Ordner',
            $requestAnalyzerTranslator->transChoice('folder', 2, ['test-1'], 'messages', 'de')
        );
    }
}
