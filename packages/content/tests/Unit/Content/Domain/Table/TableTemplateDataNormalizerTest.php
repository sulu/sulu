<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Unit\Content\Domain\Table;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Domain\Table\TableData;
use Sulu\Content\Domain\Table\TableTemplateDataNormalizer;

class TableTemplateDataNormalizerTest extends TestCase
{
    private TableTemplateDataNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new TableTemplateDataNormalizer();
    }

    public function testNormalizeTopLevelProperty(): void
    {
        $result = $this->normalizer->normalize([
            'specs' => [
                'head' => ['A'],
                'body' => [['value']],
            ],
        ], ['specs']);

        $this->assertSame([
            'version' => TableData::VERSION,
            'head' => ['A'],
            'body' => [
                [
                    ['text' => 'value', 'bold' => false, 'italic' => false, 'underline' => false],
                ],
            ],
        ], $result['specs']);
    }

    public function testNormalizeTableInsideBlockWithoutTouchingOtherBlocks(): void
    {
        $result = $this->normalizer->normalize([
            'contentBlocks' => [
                [
                    'type' => 'table_block',
                    'table' => [
                        'head' => ['Column'],
                        'body' => [['Cell']],
                    ],
                ],
                [
                    'type' => 'text_block',
                    'title' => 'Keep me',
                ],
            ],
        ], ['contentBlocks/table']);

        $this->assertSame('Keep me', $result['contentBlocks'][1]['title']);
        $this->assertSame('Column', $result['contentBlocks'][0]['table']['head'][0]);
        $this->assertSame('Cell', $result['contentBlocks'][0]['table']['body'][0][0]['text']);
    }

    public function testNormalizeNestedObjectProperty(): void
    {
        $result = $this->normalizer->normalize([
            'section' => [
                'table' => [
                    'head' => ['H'],
                    'body' => [['1']],
                ],
            ],
        ], ['section/table']);

        $this->assertSame('H', $result['section']['table']['head'][0]);
        $this->assertSame('1', $result['section']['table']['body'][0][0]['text']);
    }
}
