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

namespace Sulu\Article\Tests\Unit\Application\Content\DataMapper;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Infrastructure\Sulu\Content\DataMapper\AdditionalWebspacesDataMapper;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class AdditionalWebspacesDataMapperTest extends TestCase
{
    use ProphecyTrait;

    protected function getAdditionalWebspacesDataMapperInstance(): AdditionalWebspacesDataMapper
    {
        return new AdditionalWebspacesDataMapper(['default' => 'sulu-io'], ['default' => ['blog']]);
    }

    public function testMapNotImplementingInterface(): void
    {
        $dataMapper = $this->getAdditionalWebspacesDataMapperInstance();

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);

        $data = [
            'customizeWebspaceSettings' => true,
            'mainWebspace' => 'sulu-io',
            'additionalWebspaces' => ['example-com'],
        ];

        $dataMapper->map($unlocalizedDimensionContent->reveal(), $dimensionContent->reveal(), $data);

        // No methods should be called if not implementing interface
        $this->expectNotToPerformAssertions();
    }

    public function testMapCustomizeWebspaceSettingsTrue(): void
    {
        $dataMapper = $this->getAdditionalWebspacesDataMapperInstance();

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(ArticleDimensionContentInterface::class);
        $dimensionContent->setCustomizeWebspaceSettings(true)->shouldBeCalled();
        $dimensionContent->setAdditionalWebspaces(['example-com'])->shouldBeCalled()->willReturn($dimensionContent->reveal());

        $data = [
            'customizeWebspaceSettings' => true,
            'mainWebspace' => 'sulu-io',
            'additionalWebspaces' => ['example-com'],
        ];

        $dataMapper->map($unlocalizedDimensionContent->reveal(), $dimensionContent->reveal(), $data);
    }

    public function testMapCustomizeWebspaceSettingsFalse(): void
    {
        $dataMapper = $this->getAdditionalWebspacesDataMapperInstance();

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(ArticleDimensionContentInterface::class);
        $dimensionContent->setCustomizeWebspaceSettings(false)->shouldBeCalled();
        $dimensionContent->setMainWebspace('sulu-io')->shouldBeCalled();
        $dimensionContent->setAdditionalWebspaces(['blog'])->shouldBeCalled()->willReturn($dimensionContent->reveal());

        $data = [
            'customizeWebspaceSettings' => false,
            'mainWebspace' => 'sulu-io',
            'additionalWebspaces' => ['example-com'],
        ];

        $dataMapper->map($unlocalizedDimensionContent->reveal(), $dimensionContent->reveal(), $data);
    }

    public function testMapCustomizeWebspaceSettingsMissing(): void
    {
        $dataMapper = $this->getAdditionalWebspacesDataMapperInstance();

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(ArticleDimensionContentInterface::class);
        $dimensionContent->setCustomizeWebspaceSettings(false)->shouldBeCalled();
        $dimensionContent->setMainWebspace('sulu-io')->shouldBeCalled();
        $dimensionContent->setAdditionalWebspaces(['blog'])->shouldBeCalled()->willReturn($dimensionContent->reveal());
        $data = [
            'mainWebspace' => 'sulu-io',
            'additionalWebspaces' => ['example-com'],
        ];

        $dataMapper->map($unlocalizedDimensionContent->reveal(), $dimensionContent->reveal(), $data);
    }

    public function testMapWithNullValues(): void
    {
        $dataMapper = $this->getAdditionalWebspacesDataMapperInstance();

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(ArticleDimensionContentInterface::class);
        $dimensionContent->setCustomizeWebspaceSettings(true)->shouldBeCalled();
        $dimensionContent->setAdditionalWebspaces([])->shouldBeCalled()->willReturn($dimensionContent->reveal());

        $data = [
            'customizeWebspaceSettings' => true,
            'mainWebspace' => null,
            'additionalWebspaces' => null,
        ];

        $dataMapper->map($unlocalizedDimensionContent->reveal(), $dimensionContent->reveal(), $data);
    }

    public function testMapWithEmptyAdditionalWebspaces(): void
    {
        $dataMapper = $this->getAdditionalWebspacesDataMapperInstance();

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(ArticleDimensionContentInterface::class);
        $dimensionContent->setCustomizeWebspaceSettings(true)->shouldBeCalled();
        $dimensionContent->setAdditionalWebspaces([])->shouldBeCalled()->willReturn($dimensionContent->reveal());

        $data = [
            'customizeWebspaceSettings' => true,
            'mainWebspace' => 'sulu-io',
            'additionalWebspaces' => [],
        ];

        $dataMapper->map($unlocalizedDimensionContent->reveal(), $dimensionContent->reveal(), $data);
    }
}
