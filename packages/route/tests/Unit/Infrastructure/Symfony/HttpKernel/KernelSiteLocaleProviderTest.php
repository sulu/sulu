<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Unit\Infrastructure\Symfony\HttpKernel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sulu\Route\Infrastructure\Symfony\HttpKernel\KernelSiteLocaleProvider;

#[CoversClass(KernelSiteLocaleProvider::class)]
class KernelSiteLocaleProviderTest extends TestCase
{
    public function testProvideReturnsTheEnabledLocalesForEverySite(): void
    {
        $provider = new KernelSiteLocaleProvider(['en', 'de']);

        $this->assertSame(['en', 'de'], $provider->provide('sulu_io'));
        $this->assertSame(['en', 'de'], $provider->provide('other'));
    }
}
