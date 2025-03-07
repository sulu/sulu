<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\Page\Tests\Unit\Application\Merger\DataMapper;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Page\Application\DataMapper\NavigationContextDataMapper;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContent;

class NavigationContextDataMapperTest extends TestCase
{
    use ProphecyTrait;

    protected function createDataMapperInstance(): NavigationContextDataMapper
    {
        return new NavigationContextDataMapper();
    }

    public function testMapNoData(): void
    {
        $data = [];

        $page = new Page();
        $unlocalizedDimensionContent = new PageDimensionContent($page);
        $localizedDimensionContent = new PageDimensionContent($page);

        $mapper = $this->createDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertEmpty($localizedDimensionContent->getNavigationContexts());
        $this->assertEmpty($localizedDimensionContent->getNavigationContexts());
    }

    public function testMapData(): void
    {
        $data = [
            'navigationContexts' => ['main', 'footer'],
        ];

        $page = new Page();
        $unlocalizedDimensionContent = new PageDimensionContent($page);
        $localizedDimensionContent = new PageDimensionContent($page);

        $mapper = $this->createDataMapperInstance();
        $mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        self::assertEmpty($unlocalizedDimensionContent->getNavigationContexts());
        self::assertSame(['main', 'footer'], $localizedDimensionContent->getNavigationContexts());
    }
}
