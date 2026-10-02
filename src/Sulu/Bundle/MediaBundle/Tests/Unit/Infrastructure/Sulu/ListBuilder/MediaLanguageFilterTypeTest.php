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

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Infrastructure\Sulu\ListBuilder;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\ListBuilder\MediaLanguageFilterType;
use Sulu\Component\Rest\ListBuilder\Expression\ExpressionInterface;
use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\ListBuilderInterface;

class MediaLanguageFilterTypeTest extends TestCase
{
    use ProphecyTrait;

    private MediaLanguageFilterType $filterType;

    /**
     * @var ObjectProphecy<ListBuilderInterface>
     */
    private ObjectProphecy $listBuilder;

    /**
     * @var ObjectProphecy<FieldDescriptorInterface>
     */
    private ObjectProphecy $fieldDescriptor;

    protected function setUp(): void
    {
        $this->filterType = new MediaLanguageFilterType();
        $this->listBuilder = $this->prophesize(ListBuilderInterface::class);
        $this->fieldDescriptor = $this->prophesize(FieldDescriptorInterface::class);
    }

    public function testFilterByLanguages(): void
    {
        $inExpression = $this->prophesize(ExpressionInterface::class)->reveal();
        $this->listBuilder->createInExpression($this->fieldDescriptor->reveal(), ['de', 'fr'])
            ->willReturn($inExpression);
        $this->listBuilder->createIsNullExpression(Argument::cetera())->shouldNotBeCalled();
        $this->listBuilder->createOrExpression(Argument::cetera())->shouldNotBeCalled();
        $this->listBuilder->addExpression($inExpression)->shouldBeCalledOnce();

        $this->filterType->filter($this->listBuilder->reveal(), $this->fieldDescriptor->reveal(), 'de,fr');
    }

    public function testFilterByNoneOnly(): void
    {
        $isNullExpression = $this->prophesize(ExpressionInterface::class)->reveal();
        $this->listBuilder->createIsNullExpression($this->fieldDescriptor->reveal())
            ->willReturn($isNullExpression);
        $this->listBuilder->createInExpression(Argument::cetera())->shouldNotBeCalled();
        $this->listBuilder->createOrExpression(Argument::cetera())->shouldNotBeCalled();
        $this->listBuilder->addExpression($isNullExpression)->shouldBeCalledOnce();

        $this->filterType->filter($this->listBuilder->reveal(), $this->fieldDescriptor->reveal(), MediaLanguageFilterType::NONE_VALUE);
    }

    public function testFilterByLanguagesAndNoneCombinesWithOr(): void
    {
        $inExpression = $this->prophesize(ExpressionInterface::class)->reveal();
        $isNullExpression = $this->prophesize(ExpressionInterface::class)->reveal();
        $orExpression = $this->prophesize(ExpressionInterface::class)->reveal();

        $this->listBuilder->createInExpression($this->fieldDescriptor->reveal(), ['de'])
            ->willReturn($inExpression);
        $this->listBuilder->createIsNullExpression($this->fieldDescriptor->reveal())
            ->willReturn($isNullExpression);
        $this->listBuilder->createOrExpression([$inExpression, $isNullExpression])
            ->willReturn($orExpression);
        $this->listBuilder->addExpression($orExpression)->shouldBeCalledOnce();

        $this->filterType->filter($this->listBuilder->reveal(), $this->fieldDescriptor->reveal(), 'de,' . MediaLanguageFilterType::NONE_VALUE);
    }

    public function testFilterWithEmptyOptionsDoesNothing(): void
    {
        $this->listBuilder->createInExpression(Argument::cetera())->shouldNotBeCalled();
        $this->listBuilder->createIsNullExpression(Argument::cetera())->shouldNotBeCalled();
        $this->listBuilder->addExpression(Argument::cetera())->shouldNotBeCalled();

        $this->filterType->filter($this->listBuilder->reveal(), $this->fieldDescriptor->reveal(), '');
    }
}
