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

namespace Sulu\Content\Tests\Unit\Content\Application\PropertyResolver\Resolver;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\PropertyResolver\Resolver\TablePropertyResolver;

class TablePropertyResolverTest extends TestCase
{
    private TablePropertyResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new TablePropertyResolver();
    }

    public function testGetType(): void
    {
        $this->assertSame('table', TablePropertyResolver::getType());
    }

    public function testResolveEmpty(): void
    {
        $contentView = $this->resolver->resolve(null, 'en');

        $this->assertSame([
            'version' => 2,
            'head' => [],
            'body' => [],
        ], $contentView->getContent());
        $this->assertSame([], $contentView->getView());
    }

    public function testResolveNormalizesTableData(): void
    {
        $contentView = $this->resolver->resolve([
            'version' => 2,
            'head' => ['A', 'B'],
            'body' => [
                [
                    ['text' => '1', 'bold' => true, 'italic' => false, 'underline' => false],
                    'legacy',
                ],
                ['', ''],
            ],
            'options' => ['caption' => 'Prices'],
        ], 'en');

        $this->assertSame([
            'version' => 2,
            'head' => ['A', 'B'],
            'body' => [
                [
                    ['text' => '1', 'bold' => true, 'italic' => false, 'underline' => false],
                    ['text' => 'legacy', 'bold' => false, 'italic' => false, 'underline' => false],
                ],
            ],
            'options' => ['caption' => 'Prices'],
        ], $contentView->getContent());
    }
}
