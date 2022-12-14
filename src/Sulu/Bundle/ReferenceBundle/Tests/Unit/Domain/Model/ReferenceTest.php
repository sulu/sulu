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

class ReferenceTest extends TestCase
{
    public function testGetId(): void
    {
        $reference = new Reference();
        static::assertNull($reference->getId());
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

    public function testGetSetSecurityContext(): void
    {
        $reference = new Reference();
        $reference->setSecurityContext('security-context');
        static::assertSame('security-context', $reference->getSecurityContext());
    }

    public function testGetSetSecurityObjectType(): void
    {
        $reference = new Reference();
        $reference->setSecurityObjectType('security-type');
        static::assertSame('security-type', $reference->getSecurityObjectType());
    }

    public function testGetSetSecurityObjectId(): void
    {
        $reference = new Reference();
        $reference->setSecurityObjectId('security-id');
        static::assertSame('security-id', $reference->getSecurityObjectId());
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

    public function testGetSetProperty(): void
    {
        $reference = new Reference();
        $reference->setProperty('id');
        static::assertSame('id', $reference->getProperty());
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
