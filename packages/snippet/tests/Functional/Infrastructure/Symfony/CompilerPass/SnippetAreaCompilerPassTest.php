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
        // see packages/snippet/tests/Application/config/templates/snippets/snippet.xml for context
        $this->assertEquals(
            [
                'with-cache' => [
                    'title' => [
                        'en' => 'With cache',
                        'de' => 'Mit cache',
                    ],
                    'cache-invalidation' => true,
                ],
                'hotel' => [
                    'title' => [
                        'en' => 'snippet_type.hotel',
                        'de' => 'snippet_type.hotel',
                    ],
                    'cache-invalidation' => false,
                ],
                'test' => [
                    'title' => [
                        'en' => 'Menu Social Media Links',
                    ],
                    'cache-invalidation' => false,
                ],
            ],
            self::getContainer()->getParameter(SnippetAreaCompilerPass::SNIPPET_AREA_PARAM)
        );
    }
}
