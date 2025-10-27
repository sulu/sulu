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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentDataMapper\DataMapper;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\ContentDataMapper\DataMapper\LinkDataMapper;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

class LinkDataMapperTest extends TestCase
{
    private LinkDataMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new LinkDataMapper();
    }

    /**
     * @return array{ExampleDimensionContent, ExampleDimensionContent}
     */
    private function createExampleDimensionContents(): array
    {
        $example = new Example();

        return [
            new ExampleDimensionContent($example),
            new ExampleDimensionContent($example),
        ];
    }

    public function testMapNoData(): void
    {
        $data = [];

        [$unlocalizedDimensionContent, $localizedDimensionContent] = $this->createExampleDimensionContents();

        $this->mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($localizedDimensionContent->getLinkData());
    }

    public function testMapLinkSet(): void
    {
        $data = [
            'linkOn' => true,
            'linkData' => [
                'provider' => 'example',
                'href' => '019a251e-4251-7779-be14-af69441496e7',
                'anchor' => null,
                'query' => null,
            ],
        ];

        [$unlocalizedDimensionContent, $localizedDimensionContent] = $this->createExampleDimensionContents();

        $this->mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertSame([
            'provider' => 'example',
            'href' => '019a251e-4251-7779-be14-af69441496e7',
        ], $localizedDimensionContent->getLinkData());
    }

    public function testMapLinkUnset(): void
    {
        $data = [
            'linkOn' => false,
            'linkData' => [
                'provider' => 'example',
                'href' => '019a251e-4251-7779-be14-af69441496e7',
            ],
        ];

        [$unlocalizedDimensionContent, $localizedDimensionContent] = $this->createExampleDimensionContents();
        $localizedDimensionContent->setLinkData([
            'provider' => 'example',
            'href' => '019a251e-4251-7779-be14-af69441496e7',
        ]);

        $this->mapper->map($unlocalizedDimensionContent, $localizedDimensionContent, $data);

        $this->assertNull($localizedDimensionContent->getLinkData());
    }
}
