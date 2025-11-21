<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Media\TypeManager\TypeManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'sulu:media:type:update', description: 'Update all media type by the set configuration')]
class MediaTypeUpdateCommand extends Command
{
    public function __construct(
        private TypeManagerInterface $mediaTypeManager,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var array<int, array<int>> $updates */
        $updates = [];
        foreach ($this->getMimeTypesForAllMedia() as $mediaInfo) {
            $mediaTypeId = $this->mediaTypeManager->getMediaType($mediaInfo['mimeType']);
            if ($mediaInfo['type'] != $mediaTypeId) {
                $updates[$mediaTypeId][] = $mediaInfo['id'];
            }
        }

        $io = new SymfonyStyle($input, $output);
        if ([] === $updates) {
            $io->comment('Nothing to update');

            return 0;
        }

        $counter = 0;
        foreach ($updates as $mediaTypeId => $mediaIds) {
            $this->entityManager->createQueryBuilder()
                ->update(Media::class, 'media')
                ->set('media.type', ':type')
                ->where('media.id IN (:ids)')
                ->setParameter('ids', $mediaIds)
                ->setParameter('type', $mediaTypeId)
                ->getQuery()
                ->execute()
            ;

            $counter += \count($mediaIds);
        }
        $io->success(\sprintf('Updated %s media types', $counter));

        return 0;
    }

    /**
     * @return array<array{id: int, type: int, mimeType: string}>
     */
    private function getMimeTypesForAllMedia(): array
    {
        /** @var array<array{id: int, type: int, mimeType: string}> $data */
        $data = $this->entityManager->createQueryBuilder()
            ->addSelect('media.id', 'media.type', 'fileVersion.mimeType')
            ->from('SuluMediaBundle:Media', 'media')
            ->innerJoin('media.files', 'file')
            ->innerJoin('file.fileVersions', 'fileVersion', 'WITH', 'fileVersion.version = file.version')
            ->groupBy('media.id', 'media.type', 'fileVersion.mimeType')
            ->getQuery()
            ->getArrayResult()
        ;

        return $data;
    }
}
