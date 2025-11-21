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

namespace Sulu\Content\Tests\Unit\Content\Domain\Model;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Domain\Model\ExcerptInterface;
use Sulu\Content\Domain\Model\ExcerptTrait;

class ExcerptTraitTest extends TestCase
{
    protected function getExcerptInstance(): ExcerptInterface
    {
        return new class() implements ExcerptInterface {
            use ExcerptTrait;
        };
    }

    public function testGetSetExcerptTitle(): void
    {
        $model = $this->getExcerptInstance();
        $this->assertNull($model->getExcerptTitle());
        $model->setExcerptData(['title' => 'Excerpt Title']);
        $this->assertSame('Excerpt Title', $model->getExcerptTitle());
    }

    public function testGetSetExcerptDescription(): void
    {
        $model = $this->getExcerptInstance();
        $this->assertNull($model->getExcerptDescription());
        $model->setExcerptData(['description' => 'Excerpt Description']);
        $this->assertSame('Excerpt Description', $model->getExcerptDescription());
    }

    public function testGetSetExcerptMore(): void
    {
        $model = $this->getExcerptInstance();
        $this->assertNull($model->getExcerptMore());
        $model->setExcerptData(['more' => 'Excerpt More']);
        $this->assertSame('Excerpt More', $model->getExcerptMore());
    }

    public function testGetSetExcerptImageId(): void
    {
        $model = $this->getExcerptInstance();
        $this->assertNull($model->getExcerptImage());
        $model->setExcerptData(['image' => ['id' => 1]]);
        $this->assertNotNull($model->getExcerptImage());
        $this->assertSame(['id' => 1], $model->getExcerptImage());
    }

    public function testGetSetExcerptIconId(): void
    {
        $model = $this->getExcerptInstance();
        $this->assertNull($model->getExcerptIcon());
        $model->setExcerptData(['icon' => ['id' => 2]]);
        $this->assertNotNull($model->getExcerptIcon());
        $this->assertSame(['id' => 2], $model->getExcerptIcon());
    }
}
