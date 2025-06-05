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
use Sulu\Article\Application\Content\DataMapper\AdditionalWebspacesDataMapper;
use Sulu\Article\Domain\Model\AdditionalWebspacesInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class AdditionalWebspacesDataMapperTest extends TestCase
{
    use ProphecyTrait;

    protected function getAdditionalWebspacesDataMapperInstance(): AdditionalWebspacesDataMapper
    {
        return new AdditionalWebspacesDataMapper();
    }

    public function testMapNotImplementingInterface(): void
    {
        $dataMapper = $this->getAdditionalWebspacesDataMapperInstance();

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);

        $data = [
            'customizeWebspaceSettings' => true,
            'mainWebspace' => 'sulu-io',
            'additionalWebspaces' => ['example-com'],
        ];

        $dataMapper->map($dimensionContent->reveal(), $data);

        // No methods should be called if not implementing interface
        $this->assertTrue(true); // Assert test passed
    }

    public function testMapCustomizeWebspaceSettingsTrue(): void
    {
        $dataMapper = $this->getAdditionalWebspacesDataMapperInstance();

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(AdditionalWebspacesInterface::class);
        $dimensionContent->setCustomizeWebspaceSettings(true)->shouldBeCalled();
        $dimensionContent->setMainWebspace('sulu-io')->shouldBeCalled();
        $dimensionContent->setAdditionalWebspaces(['example-com'])->shouldBeCalled();

        $data = [
            'customizeWebspaceSettings' => true,
            'mainWebspace' => 'sulu-io',
            'additionalWebspaces' => ['example-com'],
        ];

        $dataMapper->map($dimensionContent->reveal(), $data);
    }

    public function testMapCustomizeWebspaceSettingsFalse(): void
    {
        $dataMapper = $this->getAdditionalWebspacesDataMapperInstance();

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(AdditionalWebspacesInterface::class);
        $dimensionContent->setCustomizeWebspaceSettings(false)->shouldBeCalled();
        $dimensionContent->setMainWebspace(null)->shouldBeCalled();
        $dimensionContent->setAdditionalWebspaces(null)->shouldBeCalled();

        $data = [
            'customizeWebspaceSettings' => false,
            'mainWebspace' => 'sulu-io',
            'additionalWebspaces' => ['example-com'],
        ];

        $dataMapper->map($dimensionContent->reveal(), $data);
    }

    public function testMapCustomizeWebspaceSettingsMissing(): void
    {
        $dataMapper = $this->getAdditionalWebspacesDataMapperInstance();

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(AdditionalWebspacesInterface::class);
        $dimensionContent->setCustomizeWebspaceSettings(false)->shouldBeCalled();
        $dimensionContent->setMainWebspace(null)->shouldBeCalled();
        $dimensionContent->setAdditionalWebspaces(null)->shouldBeCalled();

        $data = [
            'mainWebspace' => 'sulu-io',
            'additionalWebspaces' => ['example-com'],
        ];

        $dataMapper->map($dimensionContent->reveal(), $data);
    }

    public function testMapWithNullValues(): void
    {
        $dataMapper = $this->getAdditionalWebspacesDataMapperInstance();

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(AdditionalWebspacesInterface::class);
        $dimensionContent->setCustomizeWebspaceSettings(true)->shouldBeCalled();
        $dimensionContent->setMainWebspace(null)->shouldBeCalled();
        $dimensionContent->setAdditionalWebspaces(null)->shouldBeCalled();

        $data = [
            'customizeWebspaceSettings' => true,
            'mainWebspace' => null,
            'additionalWebspaces' => null,
        ];

        $dataMapper->map($dimensionContent->reveal(), $data);
    }

    public function testMapWithEmptyAdditionalWebspaces(): void
    {
        $dataMapper = $this->getAdditionalWebspacesDataMapperInstance();

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(AdditionalWebspacesInterface::class);
        $dimensionContent->setCustomizeWebspaceSettings(true)->shouldBeCalled();
        $dimensionContent->setMainWebspace('sulu-io')->shouldBeCalled();
        $dimensionContent->setAdditionalWebspaces([])->shouldBeCalled();

        $data = [
            'customizeWebspaceSettings' => true,
            'mainWebspace' => 'sulu-io',
            'additionalWebspaces' => [],
        ];

        $dataMapper->map($dimensionContent->reveal(), $data);
    }
}