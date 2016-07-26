<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;

class FileVersionMetaRepository extends EntityRepository implements FileVersionMetaRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findLatestWithoutSecurity()
    {
        $queryBuilder = $this->createQueryBuilder('fileVersionMeta')
            ->addSelect('fileVersion')
            ->addSelect('file')
            ->addSelect('media')
            ->addSelect('collection')
            ->leftJoin('fileVersionMeta.fileVersion', 'fileVersion')
            ->leftJoin('fileVersion.file', 'file')
            ->leftJoin('file.media', 'media')
            ->leftJoin('media.collection', 'collection')
            ->leftJoin(
                AccessControl::class,
                'accessControl',
                'WITH',
                'accessControl.entityClass = :entityClass AND accessControl.entityId = collection.id'
            )
            ->where('file.version = fileVersion.version')
            ->andWhere('accessControl.id is null');

        return $queryBuilder->setParameter('entityClass', Collection::class)->getQuery()->getResult();
    }

    /**
     * Returns query-builder to find file-version-meta without permissions.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return QueryBuilder
     */
    public function getQueryBuilderWithoutSecurity(QueryBuilder $queryBuilder)
    {
        $queryBuilder->addSelect('fileVersion')
            ->addSelect('file')
            ->addSelect('media')
            ->addSelect('collection')
            ->leftJoin('d.fileVersion', 'fileVersion')
            ->leftJoin('fileVersion.file', 'file')
            ->leftJoin('file.media', 'media')
            ->leftJoin('media.collection', 'collection')
            ->leftJoin(
                AccessControl::class,
                'accessControl',
                'WITH',
                'accessControl.entityClass = :entityClass AND accessControl.entityId = collection.id'
            )
            ->where('file.version = fileVersion.version')
            ->andWhere('accessControl.id is null')
            ->setParameter('entityClass', Collection::class);

        return $queryBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function findByCollectionId($collectionId)
    {
        $queryBuilder = $this->createQueryBuilder('fileVersionMeta')
            ->leftJoin('fileVersionMeta.fileVersion', 'fileVersion')
            ->leftJoin('fileVersion.file', 'file')
            ->leftJoin('file.media', 'media')
            ->leftJoin('media.collection', 'collection')
            ->where('collection.id = :collectionId');

        $queryBuilder->setParameter('collectionId', $collectionId);

        return $queryBuilder->getQuery()->getResult();
    }
}
