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

namespace Sulu\Content\Tests\Unit\Content\Application\ContentResolver\Resolver;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Application\ContentResolver\Resolver\DimensionContentResolver;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Symfony\Component\PropertyAccess\PropertyAccess;

class DimensionContentResolverTest extends TestCase
{
    public function testResolveWithEmptyProperties(): void
    {
        $resolver = new DimensionContentResolver(PropertyAccess::createPropertyAccessor());

        self::assertNull($resolver->resolve(
            $this->createDimensionContent(),
            [],
        ));
    }

    public function testResolveWithPropertiesNotReadable(): void
    {
        $resolver = new DimensionContentResolver(PropertyAccess::createPropertyAccessor());

        $contentView = $resolver->resolve(
            $this->createDimensionContent(),
            ['locale' => 'object.locale', 'nonExistent' => 'object.nonExistent'],
        );

        self::assertInstanceOf(ContentView::class, $contentView);
        $content = $contentView->getContent();
        self::assertIsArray($content);
        self::assertArrayHasKey('locale', $content);
        self::assertArrayHasKey('nonExistent', $content);
        self::assertNull($content['nonExistent']);
    }

    public function testResolve(): void
    {
        $resolver = new DimensionContentResolver(PropertyAccess::createPropertyAccessor());

        $dimensionContent = $this->createDimensionContent();
        $dimensionContent->setLocale('en');

        $contentView = $resolver->resolve($dimensionContent);

        self::assertInstanceOf(ContentView::class, $contentView);
        $content = $contentView->getContent();
        self::assertIsArray($content);
        self::assertArrayHasKey('id', $content);
    }

    private function createDimensionContent(): ExampleDimensionContent
    {
        $example = new Example();
        $dimensionContent = new ExampleDimensionContent($example);
        $example->addDimensionContent($dimensionContent);

        return $dimensionContent;
    }
}
