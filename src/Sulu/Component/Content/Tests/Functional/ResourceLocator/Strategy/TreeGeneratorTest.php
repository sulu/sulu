<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\ResourceLocator\Strategy;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\TreeGenerator;
use Symfony\Cmf\Api\Slugifier\SlugifierInterface;

class TreeGeneratorTest extends SuluTestCase
{
    /**
     * @var SlugifierInterface
     */
    private $slugifier;

    /**
     * @var TreeGenerator
     */
    private $generator;

    public function setUp()
    {
        $this->slugifier = $this->getContainer()->get('sulu_document_manager.slugifier');
        $this->generator = new TreeGenerator($this->slugifier);
    }

    public function testGenerate()
    {
        $this->assertEquals('/test/title', $this->generator->generate('title', '/test'));
        $this->assertEquals('/title', $this->generator->generate('title'));
    }

    public function testGenerateLatinExtended()
    {
        $this->assertEquals('/test/rozszerzony-lacinska', $this->generator->generate('Rozszerzony łacińska', '/test'));
        $this->assertEquals('/rozszerzony-lacinska', $this->generator->generate('Rozszerzony łacińska'));
    }

    public function testGenerateNonLatin()
    {
        // ukrainian cyrillic
        $this->assertEquals('/test/testova-stattia-z-i-yi-ie-g', $this->generator->generate('Тестова стаття з і, ї, є, ґ', '/test'));
        $this->assertEquals('/testova-stattia-z-i-yi-ie-g', $this->generator->generate('Тестова стаття з і, ї, є, ґ'));

        // japanese
        $this->assertEquals('/test/tesutoji-shi', $this->generator->generate('テスト記事', '/test'));
        $this->assertEquals('/tesutoji-shi', $this->generator->generate('テスト記事'));

        // chinese (simplified)
        $this->assertEquals('/test/ce-shi-wen-zhang', $this->generator->generate('测试文章', '/test'));
        $this->assertEquals('/ce-shi-wen-zhang', $this->generator->generate('测试文章'));

        // arabic
        $this->assertEquals('/test/khtbr-lmd', $this->generator->generate('اختبار المادة', '/test'));
        $this->assertEquals('/khtbr-lmd', $this->generator->generate('اختبار المادة'));

        // hebrew
        $this->assertEquals('/test/mmr-hbdyqh', $this->generator->generate('מאמר הבדיקה', '/test'));
        $this->assertEquals('/mmr-hbdyqh', $this->generator->generate('מאמר הבדיקה'));

        // russian cyrillic
        $this->assertEquals('/test/testovaia-statia-s-io-y', $this->generator->generate('Тестовая статья с ё, ъ, ы', '/test'));
        $this->assertEquals('/testovaia-statia-s-io-y', $this->generator->generate('Тестовая статья с ё, ъ, ы'));
    }
}
