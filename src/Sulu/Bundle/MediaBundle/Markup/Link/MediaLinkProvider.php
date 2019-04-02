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

use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;

class MediaLinkProvider implements LinkProviderInterface
{
    /**
     * @var MediaRepositoryInterface
     */
    private $mediaRepository;

    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    public function __construct(
        MediaRepositoryInterface $mediaRepository,
        MediaManagerInterface $mediaManager
    ) {
        $this->mediaRepository = $mediaRepository;
        $this->mediaManager = $mediaManager;
    }

    public function getConfiguration()
    {
        return null;
    }

    public function preload(array $hrefs, $locale, $published = true)
    {
        $medias = $this->mediaRepository->findMediaDisplayInfo($hrefs, $locale);

        return array_map(function($media) {
            return new LinkItem(
                $media['id'],
                $media['title'] ?? $media['defaultTitle'],
                $this->mediaManager->getUrl($media['id'], $media['name'], $media['version']),
                true
            );
        }, $medias);
    }
}
