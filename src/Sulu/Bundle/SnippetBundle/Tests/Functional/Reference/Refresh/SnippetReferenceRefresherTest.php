<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Functional\Reference\Refresh;

use Sulu\Bundle\SnippetBundle\Reference\Refresh\SnippetReferenceRefresher;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class SnippetReferenceRefresherTest extends SuluTestCase
{
    private SnippetReferenceRefresher $snippetReferenceRefresher;

    public function setUp(): void
    {
        $this->purgeDatabase();
        $this->initPhpcr();

        $this->snippetReferenceRefresher = $this->getContainer()->get('sulu_snippet.snippet_reference_refresher');
    }

    public function testRefresh(): void
    {
        $count = 0;
        foreach ($this->snippetReferenceRefresher->refresh() as $document) {
            ++$count;
        }

        $this->assertSame(0, $count); // TODO add some tests with references
    }
}
