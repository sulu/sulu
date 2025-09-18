<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Tests\Functional\Infrastructure\Symfony\CompilerPass;

use Sulu\Bundle\TestBundle\Testing\KernelTestCase;
use Sulu\Snippet\Infrastructure\Symfony\CompilerPass\SnippetAreaCompilerPass;

class SnippetAreaCompilerPassTest extends KernelTestCase
{
    public function testWithoutAreas(): void
    {
        dd(self::getContainer()->getParameter(SnippetAreaCompilerPass::SNIPPET_AREA_PARAM));
    }
}
