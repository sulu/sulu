<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Twig;

use Sulu\Bundle\MediaBundle\Api\Media as MediaApi;
use Sulu\Bundle\MediaBundle\Entity\Media as MediaEntity;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;

/**
 * Extension to handle medias in frontend.
 */
class MediaTwigExtension extends \Twig_Extension
{
    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @param MediaManagerInterface $mediaManager
     */
    public function __construct(MediaManagerInterface $mediaManager)
    {
        $this->mediaManager = $mediaManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_resolve_media', [$this, 'resolveMediaFunction']),
            new \Twig_SimpleFunction('sulu_resolve_medias', [$this, 'resolveMediasFunction']),
        ];
    }

    /**
     * resolves media id or object.
     *
     * @param int|MediaEntity $media id to resolve
     * @param string $locale
     *
     * @return MediaApi
     */
    public function resolveMediaFunction($media, $locale)
    {
        if (is_object($media)) {
            return $this->resolveMediaObject($media, $locale);
        }

        return $this->mediaManager->getById($media, $locale);
    }

    /**
     * resolves media id or object.
     *
     * @param int[]|MediaEntity[] $medias ids to resolve
     * @param string $locale
     *z
     *
     * @return MediaApi
     */
    public function resolveMediasFunction($medias, $locale)
    {
        if (count($medias) === 0) {
            return [];
        }

        $ids = [];
        $entities = [];
        $entitiesIndex = [];
        for ($i = 0; $i < count($medias); ++$i) {
            $media = $medias[$i];

            if (is_object($media)) {
                $entities[$i] = $this->resolveMediaObject($media, $locale);
            } else {
                $ids[] = $media;
                $entitiesIndex[$media] = $i;
            }
        }

        if (count($ids) > 0) {
            foreach ($this->mediaManager->getByIds($ids, $locale) as $media) {
                $entities[$entitiesIndex[$media->getId()]] = $media;
            }
        }

        ksort($entities);

        return array_values($entities);
    }

    private function resolveMediaObject($media, $locale)
    {
        if ($media instanceof MediaEntity) {
            return $this->mediaManager->addFormatsAndUrl(
                new MediaApi($media, $locale)
            );
        } elseif ($media instanceof MediaApi) {
            return $this->mediaManager->addFormatsAndUrl($media);
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_media';
    }
}
