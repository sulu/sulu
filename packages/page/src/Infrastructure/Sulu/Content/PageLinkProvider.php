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

use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfiguration;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfigurationBuilder;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderInterface;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageLinkProvider implements LinkProviderInterface
{
    public function __construct(
        private readonly ContentManagerInterface $contentManager,
        private readonly PageRepositoryInterface $pageRepository,
        private readonly ReferenceStoreInterface $pageReferenceStore,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getConfiguration(): LinkConfiguration
    {
        return LinkConfigurationBuilder::create()
            ->setTitle($this->translator->trans('sulu_page.pages', [], 'admin'))
            ->setResourceKey(PageInterface::RESOURCE_KEY)
            ->setListAdapter('table')
            ->setDisplayProperties(['id'])
            ->setOverlayTitle($this->translator->trans('sulu_page.selection_overlay_title', [], 'admin'))
            ->setEmptyText($this->translator->trans('sulu_page.no_page_selected', [], 'admin'))
            ->setIcon('su-document')
            ->getLinkConfiguration();
    }

    public function preload(array $hrefs, $locale, $published = true)
    {
        $dimensionAttributes = [
            'locale' => $locale,
            'stage' => $published ? DimensionContentInterface::STAGE_LIVE : DimensionContentInterface::STAGE_DRAFT,
        ];

        $pages = $this->pageRepository->findBy(
            filters: [...$dimensionAttributes, 'uuids' => $hrefs],
            selects: [PageRepositoryInterface::GROUP_SELECT_PAGE_WEBSITE => true]
        );

        $result = [];
        foreach ($pages as $page) {
            $dimensionContent = $this->contentManager->resolve($page, $dimensionAttributes);
            $this->pageReferenceStore->add($page->getId());

            /** @var string|null $url */
            $url = $dimensionContent->getTemplateData()['url'] ?? null;
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
