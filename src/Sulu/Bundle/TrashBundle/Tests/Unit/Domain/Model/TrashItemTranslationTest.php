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

namespace Sulu\Bundle\TrashBundle\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItem;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemTranslation;

class TrashItemTranslationTest extends TestCase
{
    private function createTrashItemTranslation(): TrashItemTranslation
    {
        return new TrashItemTranslation(
            new TrashItem(),
            null,
            ''
        );
    }

    public function testGetSetTrashItem(): void
    {
        $trashItem = new TrashItem();

        $translation = $this->createTrashItemTranslation();
        $translation->setTrashItem($trashItem);
        static::assertSame($trashItem, $translation->getTrashItem());
    }

    public function testGetSetLocale(): void
    {
        $translation = $this->createTrashItemTranslation();
        $translation->setLocale('en');
        static::assertSame('en', $translation->getLocale());
    }

    public function testGetSetTitle(): void
    {
        $translation = $this->createTrashItemTranslation();
        $translation->setTitle('Title');
        static::assertSame('Title', $translation->getTitle());
    }
}
