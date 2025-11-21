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

namespace Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;

/**
 * @phpstan-type Media array{
 *     id: int,
 *     changed: \DateTimeImmutable,
 *     created: \DateTimeImmutable,
 *     title: string,
 *     mediaId: int,
 *     locale: string,
 * }
 *
 * @internal this class is internal no backwards compatibility promise is given for this class
 *            use Symfony Dependency Injection to override or create your own ReindexProvider instead
 */
final class AdminMediaReindexProvider implements ReindexProviderInterface
{
    /**
     * @var EntityRepository<MediaInterface>
     */
    protected EntityRepository $mediaRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
    ) {
        $repository = $entityManager->getRepository(MediaInterface::class);

        $this->mediaRepository = $repository;
    }

    public function total(): ?int
    {
        // Todo: Add correct count for multiple locales.
        return null;
    }

    public function provide(ReindexConfig $reindexConfig): \Generator
    {
        $medias = $this->loadMedias($reindexConfig->getIdentifiers());

        /** @var Media $media */
        foreach ($medias as $media) {
            yield [
                'id' => MediaInterface::RESOURCE_KEY . '__' . ((string) $media['id']) . '__' . $media['locale'],
                'resourceKey' => MediaInterface::RESOURCE_KEY,
                'resourceId' => (string) $media['id'],
                'mediaId' => (string) $media['id'],
                'changedAt' => $media['changed']->format('c'),
                'createdAt' => $media['created']->format('c'),
                'title' => $media['title'],
                'locale' => $media['locale'],
                'securityContext' => MediaAdmin::SECURITY_CONTEXT,
            ];
        }
    }

    /**
     * @param string[] $identifiers
     *
     * @return iterable<Media>
     */
    private function loadMedias(array $identifiers = []): iterable
    {
        $qb = $this->mediaRepository->createQueryBuilder('media')
            ->select('media.id')
            ->addSelect('media.created')
            ->addSelect('media.changed')
            ->addSelect('file.id as fileId')
            ->addSelect('fileVersions.id as versionId')
            ->addSelect('meta.locale as locale')
            ->addSelect('meta.title as title')
            ->distinct()->join('media.files', 'file')
            ->innerJoin('file.fileVersions', 'fileVersions')
            ->innerJoin('fileVersions.meta', 'meta');

        if (0 < \count($identifiers)) {
            $conditions = [];
            $parameters = [];

            foreach ($identifiers as $index => $identifier) {
                $resourceKey = \explode('__', $identifier)[0];

                if (MediaInterface::RESOURCE_KEY !== $resourceKey) {
                    continue;
                }

                $id = \explode('__', $identifier)[1] ?? '';
                $locale = \explode('__', $identifier)[2] ?? '';

                $conditions[] = "(media.id = :id{$index} AND meta.locale = :locale{$index})";
                $parameters["id{$index}"] = $id;
                $parameters["locale{$index}"] = $locale;
            }

            if (!$conditions) {
                return [];
            }

            $qb->where(\implode(' OR ', $conditions));
            $qb->setParameters($parameters);
        }

        /** @var iterable<Media> */
        return $qb->getQuery()->toIterable();
    }

    public static function getIndex(): string
    {
        return 'admin';
    }
}
