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

namespace Sulu\Page\Infrastructure\Sulu\Content;

use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfigurationBuilder;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentEnhancer\ContentEnhancerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Webmozart\Assert\Assert;

/**
 * @interal This class is an integration to the SuluMarkupBundle and can be changed any time.
 *          Use Symfony dependency injection service decoration and change the behaviour if you need.
 */
final class PageLinkProvider implements LinkProviderInterface
{
    public function __construct(
        private readonly ContentAggregatorInterface $contentAggregator,
        private readonly ContentEnhancerInterface $contentEnhancer,
        private readonly PageRepositoryInterface $pageRepository,
        private readonly ReferenceStoreInterface $referenceStore,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getConfigurationBuilder(): LinkConfigurationBuilder
    {
        return LinkConfigurationBuilder::create()
            ->setTitle($this->translator->trans('sulu_page.pages', [], 'admin'))
            ->setResourceKey(PageInterface::RESOURCE_KEY)
            ->setListAdapter('column_list')
            ->setDisplayProperties(['title'])
            ->setOverlayTitle($this->translator->trans('sulu_page.single_selection_overlay_title', [], 'admin'))
            ->setEmptyText($this->translator->trans('sulu_page.no_page_selected', [], 'admin'))
            ->setIcon('su-document');
    }

    public function preload(array $hrefs, string $locale, bool $published = true): iterable
    {
        $dimensionAttributes = [
            'locale' => $locale,
            'stage' => $published ? DimensionContentInterface::STAGE_LIVE : DimensionContentInterface::STAGE_DRAFT,
            'version' => DimensionContentInterface::CURRENT_VERSION,
        ];

        $pages = $this->pageRepository->findBy(
            filters: [
                'uuids' => $hrefs,
                'locale' => $locale,
                'stage' => $dimensionAttributes['stage'],
                'version' => DimensionContentInterface::CURRENT_VERSION,
            ],
            selects: [
                PageRepositoryInterface::SELECT_PAGE_CONTENT => [
                    DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true,
                ],
            ]
        );

        $result = [];
        foreach ($pages as $page) {
            $dimensionContent = $this->contentAggregator->aggregate($page, $dimensionAttributes);
            $dimensionContent = $this->contentEnhancer->enhance($dimensionContent);
            Assert::isInstanceOf($dimensionContent, PageDimensionContentInterface::class);

            $this->referenceStore->add($page->getId(), PageInterface::RESOURCE_KEY);

            /** @var array<string, mixed> $templateData */
            $templateData = $dimensionContent->getTemplateData();
            /** @var string|null $url */
            $url = $templateData['url'] ?? null;
            if (null === $url) {
                // TODO what to do when there is no url?
                continue;
            }

            $result[] = new LinkItem(
                $page->getUuid(),
                (string) $dimensionContent->getTitle(),
                $url,
                $published
            );
        }

        return $result;
    }
}
