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
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;

class ResolvableResourceTest extends TestCase
{
    public function testGetId(): void
    {
        $resolvableResource = new ResolvableResource(5, 'resourceLoaderKey', 0);

        self::assertSame(5, $resolvableResource->getId());
    }

    public function testGetResourceLoaderKey(): void
    {
        $resolvableResource = new ResolvableResource(5, 'resourceLoaderKey', 0);

        self::assertSame('resourceLoaderKey', $resolvableResource->getResourceLoaderKey());
    }

    public function testGetResourceKeyReturnsNullByDefault(): void
    {
        $resolvableResource = new ResolvableResource(5, 'resourceLoaderKey', 0);

        self::assertNull($resolvableResource->getResourceKey());
    }

    public function testGetResourceKeyReturnsProvidedValue(): void
    {
        $resolvableResource = new ResolvableResource(5, 'resourceLoaderKey', 0, null, null, 'pages');

        self::assertSame('pages', $resolvableResource->getResourceKey());
    }
}
