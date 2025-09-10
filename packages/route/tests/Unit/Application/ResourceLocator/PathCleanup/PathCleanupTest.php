<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Unit\Application\ResourceLocator\PathCleanup;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Sulu\Route\Application\ResourceLocator\PathCleanup\PathCleanup;
use Sulu\Route\Application\ResourceLocator\PathCleanup\PathCleanupInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[CoversClass(PathCleanup::class)]
class PathCleanupTest extends TestCase
{
    private PathCleanupInterface $pathCleanup;

    public function setUp(): void
    {
        $slugger = new AsciiSlugger();
        $slugger = $slugger->withEmoji();

        $this->pathCleanup = new PathCleanup($slugger);
    }

    #[TestWith(['-Hello World-', 'hello-world'])]
    #[TestWith(['Hello--World', 'hello-world'])]
    #[TestWith(['Hello//World', 'hello/world'])]
    #[TestWith(['Hello / World', 'hello/world'])]
    public function testSlashAndDashes(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->pathCleanup->cleanup($input, 'en'));
    }

    #[TestWith(['Hello World', 'hello-world'])]
    #[TestWith(['Hello.World', 'hello-world'])]
    #[TestWith(['Hello^World', 'hello-world'])]
    #[TestWith(['Hello~World', 'hello-world'])]
    #[TestWith(['Hello[World', 'hello-world'])]
    #[TestWith(['Hello]World', 'hello-world'])]
    #[TestWith(['Hello)World', 'hello-world'])]
    #[TestWith(['Hello(World', 'hello-world'])]
    #[TestWith(['Hello}World', 'hello-world'])]
    #[TestWith(['Hello{World', 'hello-world'])]
    public function testSpecialChars(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->pathCleanup->cleanup($input, 'en'));
    }

    #[TestWith(['Hello WÁrld', 'hello-warld'])]
    #[TestWith(['Hállo World', 'hallo-world'])]
    #[TestWith(['HÉllo World', 'hello-world'])]
    #[TestWith(['HĬllo World', 'hillo-world'])]
    #[TestWith(['Helloř', 'hellor'])]
    #[TestWith(['Straße', 'strasse'])]
    public function testSlugger(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->pathCleanup->cleanup($input, 'en'));
    }

    #[TestWith(['The 🍕 and 🍝 World', 'the-pizza-and-spaghetti-world'])]
    public function testEmoji(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->pathCleanup->cleanup($input, 'en'));
    }

    #[TestWith(['Hallo & Welt', 'hallo-und-welt'])]
    #[TestWith(['Hällö Welt', 'haelloe-welt'])]
    public function testReplacers(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->pathCleanup->cleanup($input, 'de'));
    }

    public function testSulu26Replacers(): void
    {
        $content = \file_get_contents(__DIR__ . '/resources/replacers-26.xml');
        self::assertIsString($content, 'Could not read replacers file');
        $crawler = new Crawler($content);
        $crawler = $crawler->filter('item');

        /** @var array<string, array<string, string>> $fromToPerLocale */
        $fromToPerLocale = [];
        $crawler->each(function(Crawler $node) use (&$fromToPerLocale) {
            $locale = $node->filter('column[name=locale]')->text(null, false);
            $from = $node->filter('column[name=from]')->text(null, false);
            $to = $node->filter('column[name=to]')->text(null, false);

            $fromToPerLocale[$locale][$from] = $to;
        });

        foreach ($fromToPerLocale as $locale => $fromTo) {
            foreach ($fromTo as $from => $to) {
                // to avoid strip away of leading or trailing dashes
                $usedFrom = 'x' . $from . 'z';
                $usedTo = \str_replace(['и'], ['i'], 'x' . $to . 'z');

                $usedLocale = \strtolower($locale);
                if ('default' === $locale) {
                    $usedLocale = 'en';
                }

                $this->assertSame(
                    \mb_strtolower($usedTo),
                    $this->pathCleanup->cleanup($usedFrom, $usedLocale),
                    \sprintf('Failed asserting that "%s" is cleaned up to "%s" for locale "%s"', $from, $to, $locale),
                );
            }
        }
    }
}
