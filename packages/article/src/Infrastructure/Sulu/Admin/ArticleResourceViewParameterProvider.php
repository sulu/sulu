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

namespace Sulu\Article\Infrastructure\Sulu\Admin;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ResourceViewParameterProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * Provides the template group of an article for its `sulu_article.article.edit_tabs_{group}` view.
 *
 * @internal this class is internal no backwards compatibility promise is given for this class
 *            use Symfony Dependency Injection to override or create your own ResourceViewParameterProvider instead
 */
final class ArticleResourceViewParameterProvider implements ResourceViewParameterProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GroupProviderInterface $groupProvider,
    ) {
    }

    public static function getResourceKey(): string
    {
        return ArticleInterface::RESOURCE_KEY;
    }

    public function getViewParameters(string $resourceView, array $viewParameters): array
    {
        if ('detail' !== $resourceView) {
            return [];
        }

        $templateKey = isset($viewParameters['id'])
            ? $this->findTemplateKey((string) $viewParameters['id'], isset($viewParameters['locale']) ? (string) $viewParameters['locale'] : null)
            : null;

        foreach ($this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE) as $group) {
            if (\in_array($templateKey, $group->templates, true)) {
                return ['group' => $group->identifier];
            }
        }

        return ['group' => GroupProviderInterface::DEFAULT_GROUP];
    }

    private function findTemplateKey(string $id, ?string $locale): ?string
    {
        $queryBuilder = $this->entityManager->getRepository(ArticleDimensionContentInterface::class)
            ->createQueryBuilder('dimensionContent')
            ->select('dimensionContent.templateKey')
            ->where('dimensionContent.article = :id')
            ->andWhere('dimensionContent.stage = :stage')
            ->andWhere('dimensionContent.version = :version')
            ->andWhere('dimensionContent.templateKey IS NOT NULL')
            ->setParameter('id', $id)
            ->setParameter('stage', DimensionContentInterface::STAGE_DRAFT)
            ->setParameter('version', DimensionContentInterface::CURRENT_VERSION)
            ->setMaxResults(1);

        if (null !== $locale) {
            $queryBuilder->andWhere('dimensionContent.locale = :locale')
                ->setParameter('locale', $locale);
        } else {
            $queryBuilder->orderBy('dimensionContent.locale');
        }

        /** @var string|null $templateKey */
        $templateKey = $queryBuilder->getQuery()->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);

        return $templateKey;
    }
}
