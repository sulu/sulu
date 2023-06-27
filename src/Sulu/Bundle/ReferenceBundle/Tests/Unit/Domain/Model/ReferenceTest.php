<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Sulu\Bundle\ReferenceBundle\Domain\Model\Reference;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;

class ReferenceTest extends TestCase
{
    use SetGetPrivatePropertyTrait;

    public function testGetId(): void
    {
        $reference = new Reference();
        static::setPrivateProperty($reference, 'id', 1);
        static::assertSame(1, $reference->getId());
    }

    public function testGetSetResourceKey(): void
    {
        $reference = new Reference();
        $reference->setResourceKey('pages');
        static::assertSame('pages', $reference->getResourceKey());
    }

    public function testGetSetResourceId(): void
    {
        $reference = new Reference();
        $reference->setResourceId('123-123');
        static::assertSame('123-123', $reference->getResourceId());
    }

    public function testGetSetReferenceResourceKey(): void
    {
        $reference = new Reference();
        $reference->setReferenceResourceKey('pages');
        static::assertSame('pages', $reference->getReferenceResourceKey());
    }

    public function testGetSetReferenceResourceId(): void
    {
        $reference = new Reference();
        $reference->setReferenceResourceId('321-123');
        static::assertSame('321-123', $reference->getReferenceResourceId());
    }

    public function testGetSetReferenceTitle(): void
    {
        $reference = new Reference();
        $reference->setReferenceTitle('Title');
        static::assertSame('Title', $reference->getReferenceTitle());
    }

    public function testGetSetReferenceLocale(): void
    {
        $reference = new Reference();
        $reference->setReferenceLocale('de');
        static::assertSame('de', $reference->getReferenceLocale());
    }

    public function testGetSetReferenceViewAttributes(): void
    {
        $reference = new Reference();
        static::assertSame([], $reference->getReferenceViewAttributes());
        $reference->setReferenceViewAttributes(['locale' => 'en']);
        static::assertSame(['locale' => 'en'], $reference->getReferenceViewAttributes());
    }

    public function testGetSetReferenceProperty(): void
    {
        $reference = new Reference();
        $reference->setReferenceProperty('id');
        static::assertSame('id', $reference->getReferenceProperty());
    }

    public function testGetSetReferenceCount(): void
    {
        $reference = new Reference();
        $reference->setReferenceCount(1);
        static::assertSame(1, $reference->getReferenceCount());
        $reference->increaseReferenceCounter();
        static::assertSame(2, $reference->getReferenceCount());
    }

    public function testGetSetReferenceLiveCount(): void
    {
        $reference = new Reference();
        $reference->setReferenceLiveCount(1);
        static::assertSame(1, $reference->getReferenceLiveCount());
        $reference->increaseReferenceLiveCounter();
        static::assertSame(2, $reference->getReferenceLiveCount());
    }

    public function testEqualsTrue(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $reference1 = new Reference();
        $reference1->setResourceKey('media');
        $reference1->setResourceId('1');
        $reference1->setReferenceLocale('en');
        $reference1->setReferenceResourceKey('pages');
        $reference1->setReferenceResourceId($uuid);
        $reference1->setReferenceProperty('image');
        $reference1->setReferenceViewAttributes(['webspace' => 'default']);
        $reference2 = new Reference();
        $reference2->setResourceKey('media');
        $reference2->setResourceId('1');
        $reference2->setReferenceLocale('en');
        $reference2->setReferenceResourceKey('pages');
        $reference2->setReferenceResourceId($uuid);
        $reference2->setReferenceProperty('image');
        $reference2->setReferenceViewAttributes(['webspace' => 'default']);

        $this->assertTrue(
            $reference1->equals($reference2)
        );
    }

    public function testEqualsFalse(): void
    {
        $uuid = Uuid::uuid4()->toString();
        $uuid2 = Uuid::uuid4()->toString();
        $reference1 = new Reference();
        $reference1->setReferenceResourceId($uuid);
        $reference2 = new Reference();
        $reference2->setReferenceResourceId($uuid2);

        $this->assertFalse(
            $reference1->equals($reference2)
        );
    }
}
