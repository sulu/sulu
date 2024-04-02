<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Functional\Reference\Refresh;

use Sulu\Bundle\PageBundle\Reference\Refresh\PageReferenceRefresher;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class PageReferenceRefresherTest extends SuluTestCase
{
    private PageReferenceRefresher $pageReferenceRefresher;

    public function setUp(): void
    {
        $this->purgeDatabase();
        $this->initPhpcr();

        $this->pageReferenceRefresher = $this->getContainer()->get('sulu_page.page_reference_refresher');
    }

    public function testRefresh(): void
    {
        $count = 0;
        foreach ($this->pageReferenceRefresher->refresh() as $document) {
            ++$count;
        }

        $this->assertSame(0, $count); // TODO add some tests with references
    }
}
