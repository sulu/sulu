<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Markup\Link;

use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfigurationBuilder;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @interal This class is an integration to the SuluMarkupBundle and can be changed any time.
 *          Use Symfony dependency injection service decoration and change the behaviour if you need.
 */
final class MediaLinkProvider implements LinkProviderInterface
{
    public function __construct(
        private MediaRepositoryInterface $mediaRepository,
        private MediaManagerInterface $mediaManager,
        private TranslatorInterface $translator,
        private ReferenceStoreInterface $referenceStore,
    ) {
    }

    public function getConfigurationBuilder(): LinkConfigurationBuilder
    {
        return LinkConfigurationBuilder::create()
            ->setTitle($this->translator->trans('sulu_media.media', [], 'admin'))
            ->setResourceKey('media')
            ->setDisplayProperties(['title'])
            ->setListAdapter('')
            ->setOverlayTitle('')
            ->setEmptyText('')
            ->setIcon('');
    }

    public function preload(array $hrefs, string $locale, bool $published = true): iterable
    {
        /** @var list<array{id: int, title: string|null, defaultTitle: string, name: string, version: int}> $medias */
        $medias = $this->mediaRepository->findMediaDisplayInfo($hrefs, $locale);

        return \array_map(function(array $media) {
            $this->referenceStore->add((string) $media['id'], MediaInterface::RESOURCE_KEY);

            return new LinkItem(
                (string) $media['id'],
                $media['title'] ?? $media['defaultTitle'],
                $this->mediaManager->getUrl($media['id'], $media['name'], $media['version']),
                true
            );
        }, $medias);
    }
}
