<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Url;

use Sulu\Component\Webspace\Url\Replacer;

class ReplacerTest extends \PHPUnit_Framework_TestCase
{
    public function testHasLanguageReplacer()
    {
        $replacer = new Replacer('sulu.io');
        $this->assertFalse($replacer->hasLanguageReplacer('sulu.io'));
        $this->assertTrue($replacer->hasLanguageReplacer('sulu.io/{language}'));
    }

    public function testHasCountryReplacer()
    {
        $replacer = new Replacer();
        $this->assertFalse($replacer->hasCountryReplacer('sulu.io'));
        $this->assertTrue($replacer->hasCountryReplacer('sulu.io/{country}'));
    }

    public function testHasLocalizationReplacer()
    {
        $replacer = new Replacer();
        $this->assertFalse($replacer->hasLocalizationReplacer('sulu.io'));
        $this->assertTrue($replacer->hasLocalizationReplacer('sulu.io/{localization}'));
    }

    public function testHasSegmentReplacer()
    {
        $replacer = new Replacer();
        $this->assertFalse($replacer->hasSegmentReplacer('sulu.io'));
        $this->assertTrue($replacer->hasSegmentReplacer('sulu.io/{segment}'));
    }

    public function testReplaceCountry()
    {
        $replacer = new Replacer();
        $this->assertEquals(
            'sulu.io/at/{language}',
            $replacer->replaceCountry('sulu.io/{country}/{language}', 'at')
        );
    }

    public function testReplaceLanguage()
    {
        $replacer = new Replacer();
        $this->assertEquals('sulu.io/{country}/de', $replacer->replaceLanguage('sulu.io/{country}/{language}', 'de'));
    }

    public function testReplaceLocalization()
    {
        $replacer = new Replacer();
        $this->assertEquals('sulu.io/de_at', $replacer->replaceLocalization('sulu.io/{localization}', 'de_at'));
    }

    public function testReplaceSegment()
    {
        $replacer = new Replacer();
        $this->assertEquals('sulu.io/winter', $replacer->replaceSegment('sulu.io/{segment}', 'winter'));
    }

    public function testCleanup()
    {
        $replacer = new Replacer();
        $this->assertEquals('sulu.io', $replacer->cleanup('sulu.io/{segment}/{language}/{country}'));
        $this->assertEquals('sulu.io/test', $replacer->cleanup('sulu.io/{localization}/test'));
    }

    public function testAppendLocalizationReplacer()
    {
        $replacer = new Replacer();
        $this->assertEquals(
            'sulu.io/{segment}/{localization}',
            $replacer->appendLocalizationReplacer('sulu.io/{segment}')
        );
    }
}
