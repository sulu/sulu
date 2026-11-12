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

namespace Sulu\Redirect\Tests\Functional\Infrastructure\Symfony\HttpKernel;

use PHPUnit\Framework\Attributes\CoversClass;
use Sulu\Bundle\TestBundle\Testing\KernelTestCase;
use Sulu\Redirect\Infrastructure\Symfony\HttpKernel\SuluRedirectBundle;

#[CoversClass(SuluRedirectBundle::class)]
class SuluRedirectBundleTest extends KernelTestCase
{
    public function testParameters(): void
    {
        $this->assertSame('Sulu\Redirect\Domain\Model\Redirect', self::getContainer()->getParameter('sulu.model.redirect.class'));
    }
}
