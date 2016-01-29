<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Url;

class ReplacerTest extends \PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        $replacer = new Replacer('sulu.io');
        self::assertEquals('sulu.io', $replacer->get());
    }

    public function testHasLanguageReplacer()
    {
        $replacer = new Replacer('sulu.io');
        self::assertFalse($replacer->hasLanguageReplacer());

        $replacer = new Replacer('sulu.io/{language}');
        self::assertTrue($replacer->hasLanguageReplacer());
    }

    public function testHasCountryReplacer()
    {
        $replacer = new Replacer('sulu.io');
        self::assertFalse($replacer->hasCountryReplacer());

        $replacer = new Replacer('sulu.io/{country}');
        self::assertTrue($replacer->hasCountryReplacer());
    }

    public function testHasLocalizationReplacer()
    {
        $replacer = new Replacer('sulu.io');
        self::assertFalse($replacer->hasLocalizationReplacer());

        $replacer = new Replacer('sulu.io/{localization}');
        self::assertTrue($replacer->hasLocalizationReplacer());
    }

    public function testHasSegmentReplacer()
    {
        $replacer = new Replacer('sulu.io');
        self::assertFalse($replacer->hasSegmentReplacer());

        $replacer = new Replacer('sulu.io/{segment}');
        self::assertTrue($replacer->hasSegmentReplacer());
    }

    public function testReplaceCountry()
    {
        $replacer = new Replacer('sulu.io/{country}/{language}');
        self::assertEquals('sulu.io/at/{language}', $replacer->replaceCountry('at')->get());
    }

    public function testReplaceLanguage()
    {
        $replacer = new Replacer('sulu.io/{country}/{language}');
        self::assertEquals('sulu.io/{country}/de', $replacer->replaceLanguage('de')->get());
    }

    public function testReplaceLocalization()
    {
        $replacer = new Replacer('sulu.io/{localization}');
        self::assertEquals('sulu.io/de_at', $replacer->replaceLocalization('de_at')->get());
    }

    public function testReplaceSegment()
    {
        $replacer = new Replacer('sulu.io/{segment}');
        self::assertEquals('sulu.io/winter', $replacer->replaceSegment('winter')->get());
    }

    public function testCleanup()
    {
        $replacer = new Replacer('sulu.io/{segment}/{language}/{country}');
        self::assertEquals('sulu.io', $replacer->cleanup()->get());

        $replacer = new Replacer('sulu.io/{localization}/test');
        self::assertEquals('sulu.io/test', $replacer->cleanup()->get());
    }

    public function testAppendLocalizationReplacer()
    {
        $replacer = new Replacer('sulu.io/{segment}');
        self::assertEquals('sulu.io/{segment}/{localization}', $replacer->appendLocalizationReplacer()->get());
    }
}
