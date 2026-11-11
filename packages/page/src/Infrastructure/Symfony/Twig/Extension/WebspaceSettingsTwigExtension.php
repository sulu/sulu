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

namespace Sulu\Page\Infrastructure\Symfony\Twig\Extension;

use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Domain\Model\WebspaceSettingInterface;
use Sulu\Page\Domain\Repository\WebspaceSettingRepositoryInterface;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class WebspaceSettingsTwigExtension extends AbstractExtension implements ResetInterface
{
    /**
     * The layout of a page usually asks for the settings more than once, e.g. for the header, the footer and the
     * meta data. They are loaded once per request and reset after it, so a long running worker never keeps stale ones.
     *
     * @var array<string, array<string, mixed>|null>
     */
    private array $loaded = [];

    public function __construct(
        private WebspaceSettingRepositoryInterface $webspaceSettingRepository,
        private ContentAggregatorInterface $contentAggregator,
        private ContentResolverInterface $contentResolver,
        private RequestAnalyzerInterface $requestAnalyzer,
        private ReferenceStoreInterface $referenceStore,
        private WebspaceManagerInterface $webspaceManager,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sulu_page_webspace_settings_load', [$this, 'loadWebspaceSettings']),
        ];
    }

    /**
     * Loads the published settings of a webspace, defaults to the webspace and locale of the current request.
     *
     * @param array<string, string>|null $properties
     *
     * @return array<string, mixed>|null
     */
    public function loadWebspaceSettings(
        ?array $properties = null,
        ?string $webspaceKey = null,
        ?string $locale = null,
    ): ?array {
        $webspaceKey ??= $this->requestAnalyzer->getWebspace()?->getKey(); // @phpstan-ignore nullsafe.neverNull
        $locale ??= $this->requestAnalyzer->getCurrentLocalization()?->getLocale(); // @phpstan-ignore nullsafe.neverNull

        if (null === $webspaceKey || null === $locale) {
            return null;
        }

        $this->referenceStore->add($webspaceKey, WebspaceSettingInterface::RESOURCE_KEY);

        $cacheKey = \json_encode([$webspaceKey, $locale, $properties], \JSON_THROW_ON_ERROR);
        if (!\array_key_exists($cacheKey, $this->loaded)) {
            $this->loaded[$cacheKey] = $this->load($webspaceKey, $locale, $properties);
        }

        return $this->loaded[$cacheKey];
    }

    public function reset(): void
    {
        $this->loaded = [];
    }

    /**
     * @param array<string, string>|null $properties
     *
     * @return array<string, mixed>|null
     */
    private function load(string $webspaceKey, string $locale, ?array $properties): ?array
    {
        $webspaceSettingsForm = $this->webspaceManager->findWebspaceByKey($webspaceKey)?->getWebspaceSettingsForm();

        if (null === $webspaceSettingsForm) {
            return null;
        }

        $dimensionAttributes = [
            'locale' => $locale,
            'stage' => DimensionContentInterface::STAGE_LIVE,
            'version' => DimensionContentInterface::CURRENT_VERSION,
        ];

        $webspaceSetting = $this->webspaceSettingRepository->findOneBy(
            ['webspaceKey' => $webspaceKey, ...$dimensionAttributes],
            [
                WebspaceSettingRepositoryInterface::SELECT_WEBSPACE_SETTING_CONTENT => [
                    DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true,
                ],
            ],
        );

        if (null === $webspaceSetting) {
            return null;
        }

        $dimensionContent = $this->contentAggregator->aggregate($webspaceSetting, $dimensionAttributes);

        // The published content of a form which is no longer configured cannot be resolved, so the site keeps
        // working without settings until they are saved and published again with the current form.
        if ($webspaceSettingsForm !== $dimensionContent->getTemplateKey()) {
            return null;
        }

        return $this->contentResolver->resolve($dimensionContent, $properties);
    }
}
