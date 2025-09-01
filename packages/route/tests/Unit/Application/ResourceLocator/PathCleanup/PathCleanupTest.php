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
use Symfony\Component\String\Slugger\AsciiSlugger;

#[CoversClass(PathCleanup::class)]
class PathCleanupTest extends TestCase
{
    private PathCleanupInterface $pathCleanup;

    public function setUp(): void
    {
        $slugger = new AsciiSlugger();
        $slugger = $slugger->withEmoji();

        $this->pathCleanup = new PathCleanup(
            $slugger,
            replacers: [
                'default' => [
                    ' ' => '-',
                    '+' => '-',
                    '.' => '-',
                ],
                'de' => [
                    'ä' => 'ae',
                    'ö' => 'oe',
                    'ü' => 'ue',
                    'Ä' => 'ae',
                    'Ö' => 'oe',
                    'Ü' => 'ue',
                    'ß' => 'ss',
                    '&' => 'und',
                ],
                'en' => [
                    '&' => 'and',
                ],
                'bg' => [
                    '&' => 'и',
                ],
            ],
        );
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

    #[TestWith(['Hallo & Welt', 'hallo-und-welt'])]
    #[TestWith(['Hällö Welt', 'haelloe-welt'])]
    public function testReplacers(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->pathCleanup->cleanup($input, 'de'));
    }

    #[TestWith(['The 🍕 and 🍝 World', 'the-pizza-and-spaghetti-world'])]
    public function testEmoji(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->pathCleanup->cleanup($input, 'en'));
    }
}
