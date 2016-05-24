<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Markup;

use Sulu\Bundle\MarkupBundle\Tag\TagInterface;
use Sulu\Component\Content\Repository\Content;
use Sulu\Component\Content\Repository\ContentRepositoryInterface;
use Sulu\Component\Content\Repository\Mapping\MappingBuilder;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Extends the sulu markup with the "sulu:link" tag.
 */
class LinkTag implements TagInterface
{
    /**
     * @var ContentRepositoryInterface
     */
    private $contentRepository;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var string
     */
    private $environment;

    /**
     * @param ContentRepositoryInterface $contentRepository
     * @param RequestStack $requestStack
     * @param WebspaceManagerInterface $webspaceManager
     * @param string $environment
     */
    public function __construct(
        ContentRepositoryInterface $contentRepository,
        RequestStack $requestStack,
        WebspaceManagerInterface $webspaceManager,
        $environment
    ) {
        $this->contentRepository = $contentRepository;
        $this->requestStack = $requestStack;
        $this->webspaceManager = $webspaceManager;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function parseAll($attributesByTag)
    {
        $request = $this->requestStack->getCurrentRequest();
        $locale = $request->getLocale();

        $contents = $this->preloadContent($attributesByTag, $locale);

        $result = [];
        foreach ($attributesByTag as $tag => $attributes) {
            if (!array_key_exists($attributes['href'], $contents)) {
                $result[$tag] = array_key_exists('content', $attributes) ? $attributes['content'] :
                    (array_key_exists('title', $attributes) ? $attributes['title'] : '');

                continue;
            }

            $content = $contents[$attributes['href']];
            $url = $content->getUrl();
            $pageTitle = $content['title'];

            $title = !empty($attributes['title']) ? $attributes['title'] : $pageTitle;
            $text = !empty($attributes['content']) ? $attributes['content'] : $pageTitle;

            $url = $this->webspaceManager->findUrlByResourceLocator(
                $url,
                $this->environment,
                $locale,
                $content->getWebspaceKey(),
                null,
                $request->getScheme()
            );

            $result[$tag] = sprintf(
                '<a href="%s" title="%s"%s>%s</a>',
                $url,
                $title,
                (!empty($attributes['target']) ? ' target="' . $attributes['target'] . '"' : ''),
                $text
            );
        }

        return $result;
    }

    /**
     * Return content by uuid for given attributes.
     *
     * @param array $attributesByTag
     * @param string $locale
     *
     * @return Content[]
     */
    private function preloadContent($attributesByTag, $locale)
    {
        $uuids = array_map(
            function ($attributes) {
                return $attributes['href'];
            },
            $attributesByTag
        );

        $contents = $this->contentRepository->findByUuids(
            array_unique(array_values($uuids)),
            $locale,
            MappingBuilder::create()
                ->setResolveUrl(true)
                ->addProperties(['title'])
                ->setOnlyPublished(true)
                ->getMapping()
        );

        $result = [];
        foreach ($contents as $content) {
            $result[$content->getId()] = $content;
        }

        return $result;
    }
}
