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

use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfigurationBuilder;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MediaLinkProvider implements LinkProviderInterface
{
    public function __construct(
        private MediaRepositoryInterface $mediaRepository,
        private MediaManagerInterface $mediaManager,
        private TranslatorInterface $translator,
    ) {
    }

    public function getConfiguration()
    {
        $linkConfigurationBuilder = LinkConfigurationBuilder::create();

        return $linkConfigurationBuilder
            ->setTitle($this->translator->trans('sulu_media.media', [], 'admin'))
            ->setResourceKey('media')
            ->setDisplayProperties(['title'])
            ->setListAdapter('')
            ->setOverlayTitle('')
            ->setEmptyText('')
            ->setIcon('');
    }

    public function preload(array $hrefs, $locale, $published = true)
    {
        $medias = $this->mediaRepository->findMediaDisplayInfo($hrefs, $locale);

        return \array_map(function($media) {
            return new LinkItem(
                $media['id'],
                $media['title'] ?? $media['defaultTitle'],
                $this->mediaManager->getUrl($media['id'], $media['name'], $media['version']),
                true
            );
        }, $medias);
    }
}
