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
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\TrashBundle\Domain\Exception\TrashItemTranslationNotFoundException;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItem;

class TrashItemTest extends TestCase
{
    public function testGetId(): void
    {
        $trashItem = new TrashItem();
        static::assertNull($trashItem->getId());
    }

    public function testGetSetResourceKey(): void
    {
        $trashItem = new TrashItem();
        $trashItem->setResourceKey('tags');
        static::assertSame('tags', $trashItem->getResourceKey());
    }

    public function testGetSetResourceId(): void
    {
        $trashItem = new TrashItem();
        $trashItem->setResourceId('1');
        static::assertSame('1', $trashItem->getResourceId());
    }

    public function testGetSetRestoreData(): void
    {
        $restoreData = ['name' => 'Tag Name'];

        $trashItem = new TrashItem();
        $trashItem->setRestoreData($restoreData);
        static::assertSame($restoreData, $trashItem->getRestoreData());
    }

    public function testGetSetResourceTitle(): void
    {
        $trashItem = new TrashItem();
        $trashItem->setResourceTitle('Tag Name');
        $trashItem->setResourceTitle('Tag Name (EN)', 'en');
        static::assertSame('Tag Name', $trashItem->getResourceTitle());
        static::assertSame('Tag Name (EN)', $trashItem->getResourceTitle('en'));
        static::assertSame('Tag Name', $trashItem->getResourceTitle('de'));
    }

    public function testGetSetResourceTitleNotFound(): void
    {
        $trashItem = new TrashItem();

        static::expectException(TrashItemTranslationNotFoundException::class);
        $trashItem->getResourceTitle();
    }

    public function testGetSetResourceSecurityContext(): void
    {
        $trashItem = new TrashItem();
        $trashItem->setResourceSecurityContext('sulu.settings.tags');
        static::assertSame('sulu.settings.tags', $trashItem->getResourceSecurityContext());
    }

    public function testGetSetResourceSecurityObjectType(): void
    {
        $trashItem = new TrashItem();
        $trashItem->setResourceSecurityObjectType('tag');
        static::assertSame('tag', $trashItem->getResourceSecurityObjectType());
    }

    public function testGetSetResourceSecurityObjectId(): void
    {
        $trashItem = new TrashItem();
        $trashItem->setResourceSecurityObjectId('1');
        static::assertSame('1', $trashItem->getResourceSecurityObjectId());
    }

    public function testGetSetTimestamp(): void
    {
        $timestamp = new \DateTimeImmutable();

        $trashItem = new TrashItem();
        $trashItem->setTimestamp($timestamp);
        static::assertSame($timestamp, $trashItem->getTimestamp());
    }

    public function testGetSetUser(): void
    {
        $user = new User();

        $trashItem = new TrashItem();
        $trashItem->setUser($user);
        static::assertSame($user, $trashItem->getUser());
    }

    public function testGetTranslation(): void
    {
        $trashItem = new TrashItem();
        $trashItem->setResourceTitle('Tag Name');
        $trashItem->setResourceTitle('Tag Name (EN)', 'en');

        static::assertSame('Tag Name', $trashItem->getTranslation()->getTitle());
        static::assertNull($trashItem->getTranslation()->getLocale());

        static::assertSame('Tag Name (EN)', $trashItem->getTranslation('en')->getTitle());
        static::assertSame('en', $trashItem->getTranslation('en')->getLocale());

        static::expectException(TrashItemTranslationNotFoundException::class);
        $trashItem->getTranslation('de');
    }

    public function testGetTranslationWithFallback(): void
    {
        $trashItem = new TrashItem();
        $trashItem->setResourceTitle('Tag Name');
        $trashItem->setResourceTitle('Tag Name (EN)', 'en');

        static::assertSame('Tag Name', $trashItem->getTranslation(null, true)->getTitle());
        static::assertNull($trashItem->getTranslation(null, true)->getLocale());

        static::assertSame('Tag Name (EN)', $trashItem->getTranslation('en', true)->getTitle());
        static::assertSame('en', $trashItem->getTranslation('en', true)->getLocale());

        static::assertSame('Tag Name', $trashItem->getTranslation('de', true)->getTitle());
        static::assertNull($trashItem->getTranslation('de', true)->getLocale());
    }

    public function testGetTranslationWithoutFallback(): void
    {
        $trashItem = new TrashItem();
        $trashItem->setResourceTitle('Tag Name');
        $trashItem->setResourceTitle('Tag Name (EN)', 'en');

        static::assertSame('Tag Name', $trashItem->getTranslation(null, false)->getTitle());
        static::assertNull($trashItem->getTranslation(null, false)->getLocale());

        static::assertSame('Tag Name (EN)', $trashItem->getTranslation('en', false)->getTitle());
        static::assertSame('en', $trashItem->getTranslation('en', false)->getLocale());

        static::expectException(TrashItemTranslationNotFoundException::class);
        $trashItem->getTranslation('de', false);
    }

    public function testGetTranslationNotFound(): void
    {
        $trashItem = new TrashItem();

        static::expectException(TrashItemTranslationNotFoundException::class);
        $trashItem->getTranslation();
    }
}
