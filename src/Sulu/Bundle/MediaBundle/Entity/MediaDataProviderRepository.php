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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\MediaBundle\Api\Media as MediaApi;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryTrait;

/**
 * Implements find by filter for media data-provider.
 */
class MediaDataProviderRepository implements DataProviderRepositoryInterface
{
    use DataProviderRepositoryTrait {
        findByFilters as parentFindByFilters;
    }

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var MediaManagerInterface
     */
    private $mediaManager;

    /**
     * @var string
     */
    private $mediaEntityName;

    /**
     * @var string
     */
    private $collectionEntityName;

    public function __construct(
        EntityManagerInterface $entityManager,
        MediaManagerInterface $mediaManager,
        $mediaEntityName,
        $collectionEntityName
    ) {
        $this->entityManager = $entityManager;
        $this->mediaEntityName = $mediaEntityName;
        $this->collectionEntityName = $collectionEntityName;
        $this->mediaManager = $mediaManager;
    }

    /**
     * {@inheritdoc}
     */
    public function findByFilters($filters, $page, $pageSize, $limit, $locale, $options = [])
    {
        if (!array_key_exists('dataSource', $filters) ||
            $filters['dataSource'] === '' ||
            ($limit !== null && $limit < 1)
        ) {
            return [];
        }

        if ($filters['dataSource'] === 'root') {
            // if root collection is selected remove filter for data-source
            $filters['dataSource'] = null;
        }

        $entities = $this->parentFindByFilters($filters, $page, $pageSize, $limit, $locale, $options);

        return array_map(
            function (Media $media) use ($locale) {
                return $this->mediaManager->addFormatsAndUrl(new MediaApi($media, $locale));
            },
            $entities
        );
    }

    /**
     * {@inheritdoc}
     */
    public function appendJoins(QueryBuilder $queryBuilder, $alias, $locale)
    {
        $queryBuilder
            ->addSelect('type')
            ->addSelect('collection')
            ->addSelect('file')
            ->addSelect('tag')
            ->addSelect('fileVersion')
            ->addSelect('fileVersionMeta')
            ->addSelect('fileVersionDefaultMeta')
            ->addSelect('fileVersionContentLanguage')
            ->addSelect('fileVersionPublishLanguage')
            ->addSelect('creator')
            ->addSelect('changer')
            ->addSelect('creatorContact')
            ->addSelect('changerContact')
            ->leftJoin($alias . '.type', 'type')
            ->leftJoin($alias . '.collection', 'collection')
            ->leftJoin($alias . '.files', 'file')
            ->leftJoin('file.fileVersions', 'fileVersion', 'WITH', 'fileVersion.version = file.version')
            ->leftJoin('fileVersion.tags', 'tag')
            ->leftJoin('fileVersion.categories', 'categories')
            ->leftJoin('categories.translations', 'categoryTranslations')
            ->leftJoin('fileVersion.meta', 'fileVersionMeta')
            ->leftJoin('fileVersion.defaultMeta', 'fileVersionDefaultMeta')
            ->leftJoin('fileVersion.contentLanguages', 'fileVersionContentLanguage')
            ->leftJoin('fileVersion.publishLanguages', 'fileVersionPublishLanguage')
            ->leftJoin($alias . '.creator', 'creator')
            ->leftJoin('creator.contact', 'creatorContact')
            ->leftJoin($alias . '.changer', 'changer')
            ->leftJoin('changer.contact', 'changerContact');
    }

    /**
     * {@inheritdoc}
     */
    protected function append(QueryBuilder $queryBuilder, $alias, $locale, $options = [])
    {
        $parameter = [];

        if (array_key_exists('mimetype', $options)) {
            $queryBuilder
                ->andWhere('fileVersion.mimeType = :mimeType');

            $parameter['mimeType'] = $options['mimetype'];
        }
        if (array_key_exists('type', $options)) {
            $queryBuilder
                ->innerJoin($alias . '.type', 'type')
                ->andWhere('type.name = :type');

            $parameter['type'] = $options['type'];
        }

        return $parameter;
    }

    /**
     * {@inheritdoc}
     */
    protected function appendTagsRelation(QueryBuilder $queryBuilder, $alias)
    {
        $queryBuilder
            ->innerJoin($alias . '.files', 'file')
            ->innerJoin('file.fileVersions', 'fileVersion', 'WITH', 'fileVersion.version = file.version');

        return 'fileVersion.tags';
    }

    /**
     * {@inheritdoc}
     */
    protected function appendCategoriesRelation()
    {
        return 'fileVersion.categories';
    }

    /**
     * {@inheritdoc}
     */
    protected function appendDatasource($datasource, $includeSubFolders, QueryBuilder $queryBuilder, $alias)
    {
        if (!$includeSubFolders) {
            $queryBuilder
                ->innerJoin($alias . '.collection', 'collection')
                ->andWhere('collection.id = :collectionId');
        } else {
            $queryBuilder
                ->innerJoin(
                    $this->collectionEntityName,
                    'parentCollection',
                    Join::WITH,
                    'parentCollection.id = :collectionId'
                )
                ->innerJoin(
                    $alias . '.collection',
                    'collection',
                    Join::WITH,
                    'collection.lft BETWEEN parentCollection.lft AND parentCollection.rgt'
                );
        }

        return ['collectionId' => $datasource];
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder($alias, $indexBy = null)
    {
        return $this->entityManager->createQueryBuilder()
            ->select($alias)
            ->from($this->mediaEntityName, $alias, $indexBy);
    }
}
