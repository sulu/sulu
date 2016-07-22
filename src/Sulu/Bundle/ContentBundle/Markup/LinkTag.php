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
use Sulu\Component\Content\Document\WorkflowStage;
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
    const VALIDATE_UNPUBLISHED = 'unpublished';
    const VALIDATE_REMOVED = 'removed';

    /**
     * @var ContentRepositoryInterface
     */
    private $contentRepository;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var string
     */
    private $environment;

    /**
     * @param ContentRepositoryInterface $contentRepository
     * @param WebspaceManagerInterface $webspaceManager
     * @param RequestStack $requestStack
     * @param string $environment
     */
    public function __construct(
        ContentRepositoryInterface $contentRepository,
        WebspaceManagerInterface $webspaceManager,
        RequestStack $requestStack,
        $environment
    ) {
        $this->contentRepository = $contentRepository;
        $this->webspaceManager = $webspaceManager;
        $this->requestStack = $requestStack;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function parseAll(array $attributesByTag, $locale)
    {
        $request = $this->requestStack->getCurrentRequest();
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
     * {@inheritdoc}
     */
    public function validateAll(array $attributesByTag, $locale)
    {
        $contents = $this->preloadContent($attributesByTag, $locale, false);

        $result = [];
        foreach ($attributesByTag as $tag => $attributes) {
            if (!array_key_exists($attributes['href'], $contents)) {
                $state = self::VALIDATE_REMOVED;
            } elseif (WorkflowStage::TEST === $contents[$attributes['href']]->getWorkflowStage()) {
                $state = self::VALIDATE_UNPUBLISHED;
            } else {
                continue;
            }

            $result[$tag] = $state;
        }

        return $result;
    }

    /**
     * Return content by uuid for given attributes.
     *
     * @param array $attributesByTag
     * @param string $locale
     * @param bool $published
     *
     * @return Content[]
     */
    private function preloadContent($attributesByTag, $locale, $published = true)
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
                ->setOnlyPublished($published)
                ->setHydrateGhost(false)
                ->getMapping()
        );

        $result = [];
        foreach ($contents as $content) {
            $result[$content->getId()] = $content;
        }

        return $result;
    }
}
