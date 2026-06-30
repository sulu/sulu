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
use Sulu\Content\Domain\Table\TableCell;
use Sulu\Content\Domain\Table\TableData;
use Sulu\Content\Domain\Table\TableView;

class TableDataTest extends TestCase
{
    public function testFromArrayWithInvalidInput(): void
    {
        $data = TableData::fromArray('invalid');

        $this->assertTrue($data->isEmpty());
        $this->assertSame(TableData::VERSION, $data->version);
    }

    public function testFromArrayNormalizesRectangularMatrix(): void
    {
        $data = TableData::fromArray([
            'head' => ['A'],
            'body' => [
                ['x', ['text' => 'y', 'bold' => true]],
            ],
        ]);

        $this->assertSame(['A'], $data->head);
        $this->assertCount(1, $data->rows);
        $this->assertSame('x', $data->rows[0][0]->text);
        $this->assertTrue($data->rows[0][1]->bold);
    }

    public function testFromArrayRemovesEmptyRows(): void
    {
        $data = TableData::fromArray([
            'head' => ['A'],
            'body' => [
                ['value'],
                [''],
            ],
        ]);

        $this->assertCount(1, $data->rows);
        $this->assertSame('value', $data->rows[0][0]->text);
    }

    public function testToArrayOmitsEmptyOptions(): void
    {
        $data = TableData::fromArray([
            'head' => [],
            'body' => [],
        ]);

        $this->assertArrayNotHasKey('options', $data->toArray());
    }

    public function testTableCellClasses(): void
    {
        $cell = new TableCell('text', true, true, false);

        $this->assertSame('font-bold italic', $cell->classes());
    }

    public function testTableViewHelpers(): void
    {
        $view = TableView::fromData(TableData::fromArray([
            'head' => ['A'],
            'body' => [['1']],
            'options' => [
                'caption' => 'Caption',
                'columns' => [
                    ['align' => 'center'],
                ],
            ],
        ]));

        $this->assertFalse($view->isEmpty());
        $this->assertTrue($view->hasHead());
        $this->assertSame('Caption', $view->caption());
        $this->assertSame('text-center', $view->alignClass(0));
        $this->assertSame('text-left', $view->alignClass(1));
    }
}
