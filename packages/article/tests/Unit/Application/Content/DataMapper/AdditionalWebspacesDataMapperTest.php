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
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Article\Application\Webspace\WebspaceSettingsConfigurationResolver;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Infrastructure\Sulu\Content\DataMapper\AdditionalWebspacesDataMapper;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Sulu\Webspace\Exception\WebspaceLocaleNotSupportedException;
use Sulu\Content\Infrastructure\Sulu\Webspace\Exception\WebspaceNotFoundException;

class AdditionalWebspacesDataMapperTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<WebspaceSettingsConfigurationResolver> */
    private ObjectProphecy $configurationResolver;

    /** @var ObjectProphecy<WebspaceManagerInterface> */
    private ObjectProphecy $webspaceManager;

    protected function setUp(): void
    {
        $this->configurationResolver = $this->prophesize(WebspaceSettingsConfigurationResolver::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
    }

    protected function getAdditionalWebspacesDataMapperInstance(): AdditionalWebspacesDataMapper
    {
        return new AdditionalWebspacesDataMapper(
            $this->configurationResolver->reveal(),
            $this->webspaceManager->reveal()
        );
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
        $dimensionContent->setCustomizeWebspaceSettings(true)->shouldBeCalled()->willReturn($dimensionContent->reveal());
        $dimensionContent->getLocale()->willReturn('en');
        $dimensionContent->setAdditionalWebspaces(['example-com'])->shouldBeCalled()->willReturn($dimensionContent->reveal());

        $localization = new Localization('en');

        $mainWebspace = new Webspace();
        $mainWebspace->setKey('sulu-io');
        $mainWebspace->setName('Sulu IO');
        $mainWebspace->addLocalization($localization);
        $this->webspaceManager->findWebspaceByKey('sulu-io')->willReturn($mainWebspace);

        $additionalWebspace = new Webspace();
        $additionalWebspace->setKey('example-com');
        $additionalWebspace->setName('Example');
        $additionalWebspace->addLocalization($localization);
        $this->webspaceManager->findWebspaceByKey('example-com')->willReturn($additionalWebspace);

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

        $locale = 'en';
        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(ArticleDimensionContentInterface::class);
        $dimensionContent->getLocale()->willReturn($locale);
        $this->configurationResolver->getDefaultMainWebspaceForLocale($locale)->willReturn('sulu-io')->shouldBeCalled();
        $this->configurationResolver->getDefaultAdditionalWebspacesForLocale($locale)->willReturn(['blog'])->shouldBeCalled();
        $dimensionContent->setMainWebspace('sulu-io')->shouldBeCalled();
        $dimensionContent->setAdditionalWebspaces(['blog'])->shouldBeCalled()->willReturn($dimensionContent->reveal());
        $dimensionContent->setCustomizeWebspaceSettings(false)->shouldBeCalled()->willReturn($dimensionContent->reveal());

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

        $locale = 'en';
        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(ArticleDimensionContentInterface::class);
        $dimensionContent->getLocale()->willReturn($locale);
        $this->configurationResolver->getDefaultMainWebspaceForLocale($locale)->willReturn('sulu-io')->shouldBeCalled();
        $this->configurationResolver->getDefaultAdditionalWebspacesForLocale($locale)->willReturn(['blog'])->shouldBeCalled();
        $dimensionContent->setMainWebspace('sulu-io')->shouldBeCalled();
        $dimensionContent->setAdditionalWebspaces(['blog'])->shouldBeCalled()->willReturn($dimensionContent->reveal());
        $dimensionContent->setCustomizeWebspaceSettings(false)->shouldBeCalled()->willReturn($dimensionContent->reveal());
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
        $dimensionContent->setCustomizeWebspaceSettings(true)->shouldBeCalled()->willReturn($dimensionContent->reveal());
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
        $dimensionContent->setCustomizeWebspaceSettings(true)->shouldBeCalled()->willReturn($dimensionContent->reveal());
        $dimensionContent->getLocale()->willReturn('en');
        $dimensionContent->setAdditionalWebspaces([])->shouldBeCalled()->willReturn($dimensionContent->reveal());

        $localization = new Localization('en');
        $mainWebspace = new Webspace();
        $mainWebspace->setKey('sulu-io');
        $mainWebspace->setName('Sulu IO');
        $mainWebspace->addLocalization($localization);
        $this->webspaceManager->findWebspaceByKey('sulu-io')->willReturn($mainWebspace);

        $data = [
            'customizeWebspaceSettings' => true,
            'mainWebspace' => 'sulu-io',
            'additionalWebspaces' => [],
        ];

        $dataMapper->map($unlocalizedDimensionContent->reveal(), $dimensionContent->reveal(), $data);
    }

    public function testMapWithUnsupportedLocaleThrowsException(): void
    {
        $this->expectException(WebspaceLocaleNotSupportedException::class);
        $this->expectExceptionMessage('Webspace "example-com" does not support locale "de"');

        $dataMapper = $this->getAdditionalWebspacesDataMapperInstance();

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(ArticleDimensionContentInterface::class);
        $dimensionContent->setCustomizeWebspaceSettings(true)->shouldBeCalled()->willReturn($dimensionContent->reveal());
        $dimensionContent->getLocale()->willReturn('de');

        $localizationDe = new Localization('de');
        $mainWebspace = new Webspace();
        $mainWebspace->setKey('sulu-io');
        $mainWebspace->setName('Sulu IO');
        $mainWebspace->addLocalization($localizationDe);
        $this->webspaceManager->findWebspaceByKey('sulu-io')->willReturn($mainWebspace);

        $localizationEn = new Localization('en');
        $additionalWebspace = new Webspace();
        $additionalWebspace->setKey('example-com');
        $additionalWebspace->setName('Example');
        $additionalWebspace->addLocalization($localizationEn);
        $this->webspaceManager->findWebspaceByKey('example-com')->willReturn($additionalWebspace);

        $data = [
            'customizeWebspaceSettings' => true,
            'mainWebspace' => 'sulu-io',
            'additionalWebspaces' => ['example-com'],
        ];

        $dataMapper->map($unlocalizedDimensionContent->reveal(), $dimensionContent->reveal(), $data);
    }

    public function testMapWithSupportedLocale(): void
    {
        $dataMapper = $this->getAdditionalWebspacesDataMapperInstance();

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(ArticleDimensionContentInterface::class);
        $dimensionContent->setCustomizeWebspaceSettings(true)->shouldBeCalled()->willReturn($dimensionContent->reveal());
        $dimensionContent->getLocale()->willReturn('en');
        $dimensionContent->setAdditionalWebspaces(['example-com'])->shouldBeCalled()->willReturn($dimensionContent->reveal());

        $localizationEn = new Localization('en');

        $mainWebspace = new Webspace();
        $mainWebspace->setKey('sulu-io');
        $mainWebspace->setName('Sulu IO');
        $mainWebspace->addLocalization($localizationEn);
        $this->webspaceManager->findWebspaceByKey('sulu-io')->willReturn($mainWebspace);

        $additionalWebspace = new Webspace();
        $additionalWebspace->setKey('example-com');
        $additionalWebspace->setName('Example');
        $additionalWebspace->addLocalization($localizationEn);
        $this->webspaceManager->findWebspaceByKey('example-com')->willReturn($additionalWebspace);

        $data = [
            'customizeWebspaceSettings' => true,
            'mainWebspace' => 'sulu-io',
            'additionalWebspaces' => ['example-com'],
        ];

        $dataMapper->map($unlocalizedDimensionContent->reveal(), $dimensionContent->reveal(), $data);
    }

    public function testMapMainWebspaceNotFoundThrowsExceptionWhenCustomized(): void
    {
        $this->expectException(WebspaceNotFoundException::class);
        $this->expectExceptionMessage('Webspace "missing" not found');

        $dataMapper = $this->getAdditionalWebspacesDataMapperInstance();

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(ArticleDimensionContentInterface::class);
        $dimensionContent->setCustomizeWebspaceSettings(true)->shouldBeCalled()->willReturn($dimensionContent->reveal());
        $dimensionContent->getLocale()->willReturn('en');

        $this->webspaceManager->findWebspaceByKey('missing')->willReturn(null);

        $data = [
            'customizeWebspaceSettings' => true,
            'mainWebspace' => 'missing',
            'additionalWebspaces' => [],
        ];

        $dataMapper->map($unlocalizedDimensionContent->reveal(), $dimensionContent->reveal(), $data);
    }

    public function testMapMainWebspaceWithUnsupportedLocaleThrowsExceptionWhenCustomized(): void
    {
        $this->expectException(WebspaceLocaleNotSupportedException::class);
        $this->expectExceptionMessage('Webspace "sulu-io" does not support locale "de"');

        $dataMapper = $this->getAdditionalWebspacesDataMapperInstance();

        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(ArticleDimensionContentInterface::class);
        $dimensionContent->setCustomizeWebspaceSettings(true)->shouldBeCalled()->willReturn($dimensionContent->reveal());
        $dimensionContent->getLocale()->willReturn('de');

        $localizationEn = new Localization('en');
        $mainWebspace = new Webspace();
        $mainWebspace->setKey('sulu-io');
        $mainWebspace->setName('Sulu IO');
        $mainWebspace->addLocalization($localizationEn);
        $this->webspaceManager->findWebspaceByKey('sulu-io')->willReturn($mainWebspace);

        $data = [
            'customizeWebspaceSettings' => true,
            'mainWebspace' => 'sulu-io',
            'additionalWebspaces' => [],
        ];

        $dataMapper->map($unlocalizedDimensionContent->reveal(), $dimensionContent->reveal(), $data);
    }

    public function testMapMainWebspaceDoesNotValidateLocaleWhenNotCustomized(): void
    {
        $dataMapper = $this->getAdditionalWebspacesDataMapperInstance();

        $locale = 'de';
        $unlocalizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $dimensionContent->willImplement(ArticleDimensionContentInterface::class);
        $dimensionContent->getLocale()->willReturn($locale);
        $this->webspaceManager->findWebspaceByKey('sulu-io')->shouldNotBeCalled();
        $this->configurationResolver->getDefaultMainWebspaceForLocale($locale)->willReturn('sulu-io');
        $this->configurationResolver->getDefaultAdditionalWebspacesForLocale($locale)->willReturn([]);
        $dimensionContent->setMainWebspace('sulu-io')->shouldBeCalled();
        $dimensionContent->setAdditionalWebspaces([])->shouldBeCalled()->willReturn($dimensionContent->reveal());
        $dimensionContent->setCustomizeWebspaceSettings(false)->shouldBeCalled()->willReturn($dimensionContent->reveal());

        $data = [
            'customizeWebspaceSettings' => false,
            'mainWebspace' => 'sulu-io',
            'additionalWebspaces' => ['example-com'],
        ];

        $dataMapper->map($unlocalizedDimensionContent->reveal(), $dimensionContent->reveal(), $data);
    }
}
