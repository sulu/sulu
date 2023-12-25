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

namespace Sulu\Bundle\RouteBundle\Tests\Model;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\RouteBundle\Model\ResourceLocatorParts;

class ResourceLocatorPartsTest extends TestCase
{
    public function testGet(): void
    {
        $parts = new ResourceLocatorParts(['test' => 'value']);
        // @phpstan-ignore-next-line
        $this->assertSame('value', $parts->test);
    }

    public function testMagicGetter(): void
    {
        $parts = new ResourceLocatorParts(['test' => 'value']);
        // @phpstan-ignore-next-line
        $this->assertSame('value', $parts->getTest());
    }

    public function testArrayAccess(): void
    {
        $parts = new ResourceLocatorParts(['test' => 'value']);
        $this->assertSame('value', $parts['test']);
    }
}
