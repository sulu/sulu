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

    public function testGetSetLocale(): void
    {
        $reference = new Reference();
        $reference->setLocale('de');
        static::assertSame('de', $reference->getLocale());
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

    public function testGetSetReferenceSecurityContext(): void
    {
        $reference = new Reference();
        $reference->setReferenceSecurityContext('security-context');
        static::assertSame('security-context', $reference->getReferenceSecurityContext());
    }

    public function testGetSetReferenceSecurityObjectType(): void
    {
        $reference = new Reference();
        $reference->setReferenceSecurityObjectType('security-type');
        static::assertSame('security-type', $reference->getReferenceSecurityObjectType());
    }

    public function testGetSetReferenceSecurityObjectId(): void
    {
        $reference = new Reference();
        $reference->setReferenceSecurityObjectId('security-id');
        static::assertSame('security-id', $reference->getReferenceSecurityObjectId());
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
}
