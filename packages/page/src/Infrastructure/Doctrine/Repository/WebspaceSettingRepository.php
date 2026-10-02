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

namespace Sulu\Page\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Domain\Exception\WebspaceSettingNotFoundException;
use Sulu\Page\Domain\Model\WebspaceSettingDimensionContentInterface;
use Sulu\Page\Domain\Model\WebspaceSettingInterface;
use Sulu\Page\Domain\Repository\WebspaceSettingRepositoryInterface;

/**
 * @phpstan-import-type WebspaceSettingFilters from WebspaceSettingRepositoryInterface
 * @phpstan-import-type WebspaceSettingSelects from WebspaceSettingRepositoryInterface
 */
final class WebspaceSettingRepository implements WebspaceSettingRepositoryInterface
{
    /**
     * @var EntityRepository<WebspaceSettingInterface>
     */
    private EntityRepository $entityRepository;

    /**
     * @var class-string<WebspaceSettingInterface>
     */
    private string $webspaceSettingClassName;

    /**
     * @var class-string<WebspaceSettingDimensionContentInterface>
     */
    private string $dimensionContentClassName;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DimensionContentQueryEnhancer $dimensionContentQueryEnhancer,
    ) {
        $this->entityRepository = $entityManager->getRepository(WebspaceSettingInterface::class);
        $this->webspaceSettingClassName = $this->entityRepository->getClassName();
        $this->dimensionContentClassName = $entityManager->getRepository(WebspaceSettingDimensionContentInterface::class)->getClassName();
    }

    public function createNew(string $webspaceKey): WebspaceSettingInterface
    {
        $className = $this->webspaceSettingClassName;

        return new $className($webspaceKey);
    }

    public function getOneBy(array $filters, array $selects = []): WebspaceSettingInterface
    {
        $webspaceSetting = $this->findOneBy($filters, $selects);

        if (null === $webspaceSetting) {
            throw new WebspaceSettingNotFoundException($filters['webspaceKey'] ?? '');
        }

        return $webspaceSetting;
    }

    public function findOneBy(array $filters, array $selects = []): ?WebspaceSettingInterface
    {
        $query = $this->createQueryBuilder($filters, $selects)->getQuery();

        if ($selects[self::SELECT_WEBSPACE_SETTING_CONTENT] ?? false) {
            // The settings of a webspace are loaded once for each locale and stage. Without the refresh, the contents
            // of the first query stay on the already loaded entity and the following ones would not find their own.
            $query->setHint(Query::HINT_REFRESH, true);
        }

        try {
            /** @var WebspaceSettingInterface */
            return $query->getSingleResult();
        } catch (NoResultException) {
            return null;
        }
    }

    public function getOrNew(string $webspaceKey, array $filters = [], array $selects = []): WebspaceSettingInterface
    {
        return $this->findOneBy([...$filters, 'webspaceKey' => $webspaceKey], $selects) ?? $this->createNew($webspaceKey);
    }

    public function add(WebspaceSettingInterface $webspaceSetting): void
    {
        $this->entityManager->persist($webspaceSetting);
    }

    /**
     * @param WebspaceSettingFilters $filters
     * @param WebspaceSettingSelects $selects
     */
    private function createQueryBuilder(array $filters, array $selects = []): QueryBuilder
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('webspaceSetting');

        if (isset($filters['webspaceKey'])) {
            $queryBuilder->andWhere('webspaceSetting.webspaceKey = :webspaceKey')
                ->setParameter('webspaceKey', $filters['webspaceKey']);
        }

        if (\array_key_exists('locale', $filters) && \array_key_exists('stage', $filters)) {
            $this->dimensionContentQueryEnhancer->addFilters(
                $queryBuilder,
                'webspaceSetting',
                $this->dimensionContentClassName,
                $filters,
                [],
            );
        }

        /** @var array{dimensionAttributes?: array<string, mixed>, selects?: array<string, bool>}|bool|null $contentConfig */
        $contentConfig = $selects[self::SELECT_WEBSPACE_SETTING_CONTENT] ?? null;
        if ($contentConfig) {
            $queryBuilder->leftJoin('webspaceSetting.dimensionContents', 'dimensionContent');

            if (\is_array($contentConfig) && isset($contentConfig['dimensionAttributes'])) {
                $contentSelects = $contentConfig['selects'] ?? [];
                $dimensionAttributes = $contentConfig['dimensionAttributes'];
            } else {
                /** @var array<string, bool> $contentSelects */
                $contentSelects = \is_array($contentConfig) ? $contentConfig : [];
                $dimensionAttributes = $filters;
            }

            $this->dimensionContentQueryEnhancer->addSelects(
                $queryBuilder,
                $this->dimensionContentClassName,
                $dimensionAttributes,
                $contentSelects,
            );
        }

        return $queryBuilder;
    }
}
