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

use Sulu\Bundle\WebsiteBundle\Translator\RequestAnalyzerTranslator;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class RequestAnalyzerTranslatorTest extends \PHPUnit_Framework_TestCase
{
    public function testGetLocale()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzerTranslator = new RequestAnalyzerTranslator($translator->reveal(), $requestAnalyzer->reveal());

        $requestAnalyzer->getCurrentLocalization()->willReturn(new Localization('de'));
        $translator->setLocale('de')->shouldBeCalledTimes(1);
        $translator->getLocale()->willReturn('de');

        $this->assertEquals('de', $requestAnalyzerTranslator->getLocale());
    }

    public function testGetLocaleWithCountry()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzerTranslator = new RequestAnalyzerTranslator($translator->reveal(), $requestAnalyzer->reveal());

        $requestAnalyzer->getCurrentLocalization()->willReturn(new Localization('de', 'at'));
        $translator->setLocale('de_AT')->shouldBeCalledTimes(1);
        $translator->getLocale()->willReturn('de_AT');

        $this->assertEquals('de_AT', $requestAnalyzerTranslator->getLocale());
    }

    public function testGetLocaleTwice()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzerTranslator = new RequestAnalyzerTranslator($translator->reveal(), $requestAnalyzer->reveal());

        $requestAnalyzer->getCurrentLocalization()->willReturn(new Localization('de'));
        $translator->setLocale('de')->shouldBeCalledTimes(1);
        $translator->getLocale()->willReturn('de');

        $this->assertEquals('de', $requestAnalyzerTranslator->getLocale());
        $this->assertEquals('de', $requestAnalyzerTranslator->getLocale());
    }

    public function testSetLocale()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzerTranslator = new RequestAnalyzerTranslator($translator->reveal(), $requestAnalyzer->reveal());

        $requestAnalyzer->getCurrentLocalization()->willReturn(new Localization('de'));
        $translator->setLocale('de')->shouldNotBeCalled();
        $translator->setLocale('en')->shouldBeCalled();

        $requestAnalyzerTranslator->setLocale('en');
    }

    public function testSetLocaleTwice()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzerTranslator = new RequestAnalyzerTranslator($translator->reveal(), $requestAnalyzer->reveal());

        $requestAnalyzer->getCurrentLocalization()->willReturn(new Localization('de'));
        $translator->setLocale('de')->shouldNotBeCalled();
        $translator->setLocale('en')->shouldBeCalled();

        $requestAnalyzerTranslator->setLocale('en');
        $requestAnalyzerTranslator->setLocale('en');
    }

    public function testTrans()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzerTranslator = new RequestAnalyzerTranslator($translator->reveal(), $requestAnalyzer->reveal());

        $requestAnalyzer->getCurrentLocalization()->willReturn(new Localization('de'));
        $translator->setLocale('de')->shouldBeCalledTimes(1);
        $translator->trans('folder', ['test-1'], 'messages', 'de')->willReturn('Ordner');

        $this->assertEquals('Ordner', $requestAnalyzerTranslator->trans('folder', ['test-1'], 'messages', 'de'));
    }

    public function testTransTwice()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzerTranslator = new RequestAnalyzerTranslator($translator->reveal(), $requestAnalyzer->reveal());

        $requestAnalyzer->getCurrentLocalization()->willReturn(new Localization('de'));
        $translator->setLocale('de')->shouldBeCalledTimes(1);
        $translator->trans('folder', ['test-1'], 'messages', 'de')->willReturn('Ordner');

        $this->assertEquals('Ordner', $requestAnalyzerTranslator->trans('folder', ['test-1'], 'messages', 'de'));
        $this->assertEquals('Ordner', $requestAnalyzerTranslator->trans('folder', ['test-1'], 'messages', 'de'));
    }

    public function testTransChoice()
    {
        $translator = $this->prophesize(TranslatorInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzerTranslator = new RequestAnalyzerTranslator($translator->reveal(), $requestAnalyzer->reveal());

        $requestAnalyzer->getCurrentLocalization()->willReturn(new Localization('de'));
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
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzerTranslator = new RequestAnalyzerTranslator($translator->reveal(), $requestAnalyzer->reveal());

        $requestAnalyzer->getCurrentLocalization()->willReturn(new Localization('de'));
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
