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

namespace Sulu\Article\Infrastructure\Sulu\Security;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Admin\ArticleAdmin;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Content\Application\Security\WorkflowTransitionRequestSecurityContextResolverInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * Articles are secured by the context of their group, which `sulu_admin.resources` cannot express:
 * it declares the static `sulu.article.articles` only. The group follows from the template of the
 * article, with the same fallback to the static context as the article admin views.
 *
 * @internal
 */
final class ArticleWorkflowTransitionRequestSecurityContextResolver implements WorkflowTransitionRequestSecurityContextResolverInterface
{
    public function __construct(
        private readonly WorkflowTransitionRequestSecurityContextResolverInterface $inner,
        private readonly EntityManagerInterface $entityManager,
        private readonly GroupProviderInterface $groupProvider,
    ) {
    }

    public function resolve(string $resourceKey, string $resourceId, string $locale): SecurityCondition
    {
        if (ArticleInterface::RESOURCE_KEY !== $resourceKey) {
            return $this->inner->resolve($resourceKey, $resourceId, $locale);
        }

        $groups = $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE);
        $templateKey = 1 < \count($groups) ? $this->findTemplateKey($resourceId, $locale) : null;

        foreach ($groups as $group) {
            if (GroupProviderInterface::DEFAULT_GROUP === $group->identifier) {
                continue;
            }

            if (\in_array($templateKey, $group->templates, true)) {
                return new SecurityCondition(ArticleAdmin::getArticleSecurityContext($group->identifier), $locale);
            }
        }

        return $this->inner->resolve($resourceKey, $resourceId, $locale);
    }

    public function has(string $resourceKey): bool
    {
        return $this->inner->has($resourceKey);
    }

    private function findTemplateKey(string $resourceId, string $locale): ?string
    {
        $templateKeys = $this->entityManager->getRepository(ArticleDimensionContentInterface::class)
            ->createQueryBuilder('dimensionContent')
            ->select('dimensionContent.templateKey')
            ->where('dimensionContent.article = :id')
            ->andWhere('dimensionContent.locale = :locale')
            ->andWhere('dimensionContent.stage = :stage')
            ->andWhere('dimensionContent.version = :version')
            ->setParameter('id', $resourceId)
            ->setParameter('locale', $locale)
            ->setParameter('stage', DimensionContentInterface::STAGE_DRAFT)
            ->setParameter('version', DimensionContentInterface::CURRENT_VERSION)
            ->getQuery()
            ->getSingleColumnResult();

        $templateKey = $templateKeys[0] ?? null;

        return \is_string($templateKey) ? $templateKey : null;
    }
}
