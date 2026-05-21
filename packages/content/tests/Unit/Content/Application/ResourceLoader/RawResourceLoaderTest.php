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

namespace Sulu\Content\Tests\Unit\Content\Application\ResourceLoader;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\ResourceLoader\Loader\RawResourceLoader;

class RawResourceLoaderTest extends TestCase
{
    public function testGetKey(): void
    {
        $this->assertSame('raw', RawResourceLoader::getKey());
    }

    public function testLoadReturnsIdsKeyedByThemselves(): void
    {
        $loader = new RawResourceLoader();

        $this->assertSame([1 => 1, 2 => 2, 3 => 3], $loader->load([1, 2, 3], 'en'));
    }

    public function testLoadHandlesStringIds(): void
    {
        $loader = new RawResourceLoader();

        $this->assertSame(['a' => 'a', 'b' => 'b'], $loader->load(['a', 'b'], 'en'));
    }

    public function testLoadReturnsEmptyForEmptyInput(): void
    {
        $loader = new RawResourceLoader();

        $this->assertSame([], $loader->load([], 'en'));
    }
}
