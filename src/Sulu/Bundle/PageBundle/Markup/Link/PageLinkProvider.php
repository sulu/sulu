<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Markup\Link;

use Sulu\Component\Content\Repository\Content;
use Sulu\Component\Content\Repository\ContentRepositoryInterface;
use Sulu\Component\Content\Repository\Mapping\MappingBuilder;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Integrates pages into link-system.
 */
class PageLinkProvider implements LinkProviderInterface
{
    /**
     * @var ContentRepositoryInterface
     */
    protected $contentRepository;

    /**
     * @var WebspaceManagerInterface
     */
    protected $webspaceManager;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    protected $environment;

    public function __construct(
        ContentRepositoryInterface $contentRepository,
        WebspaceManagerInterface $webspaceManager,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        string $environment
    ) {
        $this->contentRepository = $contentRepository;
        $this->webspaceManager = $webspaceManager;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return new LinkConfiguration(
            $this->translator->trans('sulu_page.pages', [], 'admin'),
            'pages',
            'column_list',
            ['title'],
            $this->translator->trans('sulu_page.single_selection_overlay_title', [], 'admin'),
            $this->translator->trans('sulu_page.no_page_selected', [], 'admin'),
            'su-document'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function preload(array $hrefs, $locale, $published = true)
    {
        $request = $this->requestStack->getCurrentRequest();
        $scheme = 'http';
        if ($request) {
            $scheme = $request->getScheme();
        }

        $contents = $this->contentRepository->findByUuids(
            array_unique(array_values($hrefs)),
            $locale,
            MappingBuilder::create()
                ->setResolveUrl(true)
                ->addProperties(['title', 'published'])
                ->setOnlyPublished($published)
                ->setHydrateGhost(false)
                ->getMapping()
        );

        return array_map(
            function(Content $content) use ($locale, $scheme) {
                return $this->getLinkItem($content, $locale, $scheme);
            },
            $contents
        );
    }

    /**
     * Returns new link item.
     *
     * @param Content $content
     * @param string $locale
     * @param string $scheme
     *
     * @return LinkItem
     */
    protected function getLinkItem(Content $content, $locale, $scheme)
    {
        $published = !empty($content->getPropertyWithDefault('published'));
        $url = $this->webspaceManager->findUrlByResourceLocator(
            $content->getUrl(),
            $this->environment,
            $locale,
            $content->getWebspaceKey(),
            null,
            $scheme
        );

        return new LinkItem($content->getId(), $content->getPropertyWithDefault('title'), $url, $published);
    }
}
