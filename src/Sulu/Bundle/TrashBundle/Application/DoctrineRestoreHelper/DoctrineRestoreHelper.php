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

namespace Sulu\Bundle\TrashBundle\Application\DoctrineRestoreHelper;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\ORM\Mapping\ClassMetadata;

final class DoctrineRestoreHelper implements DoctrineRestoreHelperInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function persistAndFlushWithId(object $entity, $id): void
    {
        $entityClass = \get_class($entity);
        $metadata = $this->entityManager->getClassMetaData($entityClass);

        $idReflectionProperty = $metadata->getSingleIdReflectionProperty();
        $idReflectionProperty->setAccessible(true);
        $idReflectionProperty->setValue($entity, $id);

        $previousIdGeneratorType = $metadata->generatorType;
        $previousIdGenerator = $metadata->idGenerator;

        try {
            // changing the id generator for a class after an entity of the class was persisted can lead to errors
            // during flush, because the doctrine BasicEntityPersister caches the insert sql on the first insert:
            // https://github.com/doctrine/orm/blob/7fcab3d52e9fd8d1e8f6b16139ed600a2af3e953/lib/Doctrine/ORM/Persisters/Entity/BasicEntityPersister.php#L1381-L1383
            // this problem should not occur during normal usage because restoring happens in a separate request
            $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
            $metadata->setIdGenerator(new AssignedGenerator());

            $this->entityManager->persist($entity);
            $this->entityManager->flush();
        } finally {
            $metadata->setIdGeneratorType($previousIdGeneratorType);
            $metadata->setIdGenerator($previousIdGenerator);
        }
    }
}
