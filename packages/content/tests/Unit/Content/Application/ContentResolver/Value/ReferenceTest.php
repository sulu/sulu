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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentResolver\Value;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\ContentResolver\Value\Reference;

class ReferenceTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $reference = new Reference(123, 'pages');

        self::assertSame(123, $reference->getResourceId());
        self::assertSame('pages', $reference->getResourceKey());
    }

    public function testConstructorWithStringId(): void
    {
        $reference = new Reference('uuid-string', 'articles');

        self::assertSame('uuid-string', $reference->getResourceId());
        self::assertSame('articles', $reference->getResourceKey());
    }

    public function testConstructorWithIntId(): void
    {
        $reference = new Reference(456, 'contacts');

        self::assertSame(456, $reference->getResourceId());
        self::assertSame('contacts', $reference->getResourceKey());
    }
}
