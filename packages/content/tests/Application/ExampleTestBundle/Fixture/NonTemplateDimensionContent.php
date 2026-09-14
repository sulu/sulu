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

namespace Sulu\Content\Tests\Application\ExampleTestBundle\Fixture;

use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\DimensionContentTrait;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

/**
 * Dimension content that deliberately does not implement TemplateInterface. Not an entity, it only
 * exists so tests can cover the "content carries no template" branch. A Prophecy double cannot serve
 * that role because `getResourceKey()` is static.
 *
 * @implements DimensionContentInterface<Example>
 */
final class NonTemplateDimensionContent implements DimensionContentInterface
{
    use DimensionContentTrait;

    public function __construct(private Example $resource)
    {
    }

    public static function getResourceKey(): string
    {
        return Example::RESOURCE_KEY;
    }

    /**
     * @return ContentRichEntityInterface<ExampleDimensionContent>
     */
    public function getResource(): ContentRichEntityInterface
    {
        return $this->resource;
    }
}
