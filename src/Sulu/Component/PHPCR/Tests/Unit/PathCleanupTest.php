<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sulu\Component\PHPCR\PathCleanup;
use Sulu\Component\PHPCR\PathCleanupInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

class PathCleanupTest extends TestCase
{
    /**
     * @var PathCleanupInterface
     */
    private $cleaner;

    protected function setUp(): void
    {
        $this->cleaner = new PathCleanup(
            [
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
            new AsciiSlugger()
        );
    }

    /**
     * @dataProvider cleanupProvider
     */
    public function testCleanup($a, $b, $locale): void
    {
        $clean = $this->cleaner->cleanup($a, $locale);
        $this->assertEquals($b, $clean);
    }

    public function cleanupProvider()
    {
        return [
            ['-/aSDf     asdf/äöü-/hello: world\'s', '/asdf-asdf/aeoeue/hello-world-s', 'de'],
            ['it\'s+-_,.a multiple---dash test!!!', 'it-s-a-multiple-dash-test', 'en'],
            ['dash before slash -/', 'dash-before-slash/', 'en'],
            ['dash after slash /-', 'dash-after-slash/', 'en'],
            ['-dash in beginning', 'dash-in-beginning', 'en'],
            ['dash in end-', 'dash-in-end', 'en'],
            ['multiple slashes 1 ///', 'multiple-slashes-1/', 'en'],
            ['multiple slashes 2 \\\\\\', 'multiple-slashes-2', 'en'],
            ['multiple slashes 3 /\\/\\/', 'multiple-slashes-3/', 'en'],
            ['You & I', 'you-and-i', 'en'],
            ['You & I', 'you-und-i', 'de'],
            ['ти & аз', 'ti-i-az', 'bg'],
            ['шише', 'shishe', 'bg'],
            ['Горна Оряховица', 'gorna-oryakhovitsa', 'bg'],
            ['Златни пясъци', 'zlatni-pyasutsi', 'bg'],
        ];
    }

    public function testValidate(): void
    {
        $this->assertFalse($this->cleaner->validate('-/aSDf     asdf/äöü-'));
        $this->assertTrue($this->cleaner->validate('/asdf/asdf'));
        $this->assertFalse($this->cleaner->validate('  '));
        $this->assertFalse($this->cleaner->validate('/Test'));
        $this->assertFalse($this->cleaner->validate('/-test'));
        $this->assertFalse($this->cleaner->validate('/asdf.xml'));
    }
}
