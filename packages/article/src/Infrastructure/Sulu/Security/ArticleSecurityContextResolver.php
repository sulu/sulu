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
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormGroup;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * Falls back to `sulu.article.articles` for a single group or the default group, like the article admin.
 *
 * @internal
 */
final class ArticleSecurityContextResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GroupProviderInterface $groupProvider,
        private readonly MetadataProviderRegistry $metadataProviderRegistry,
    ) {
    }

    public function resolveByTemplateKey(?string $templateKey): string
    {
        return $this->resolveInGroups($this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE), $templateKey);
    }

    /**
     * The template an article gets without a submitted one, which depends on the configured default type.
     */
    public function resolveByDefaultType(string $locale): string
    {
        $metadata = $this->metadataProviderRegistry->getMetadataProvider('form')
            ->getMetadata(ArticleInterface::TEMPLATE_TYPE, $locale, []);

        return $this->resolveByTemplateKey($metadata instanceof TypedFormMetadata ? $metadata->getDefaultType() : null);
    }

    /**
     * @param array<string, FormGroup> $groups
     */
    public function resolveByGroup(FormGroup $group, array $groups): string
    {
        return ArticleAdmin::getGroupSecurityContext($groups, $group);
    }

    /**
     * The template is stored per locale, so an article can sit in more than one group.
     *
     * @param list<string> $locales all locales if empty
     *
     * @return list<string> no context if the article has no draft in these locales, the base one for a single group
     */
    public function resolveByArticle(string $articleId, array $locales = []): array
    {
        $groups = $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE);
        if (\count($groups) <= 1) {
            return [ArticleAdmin::SECURITY_CONTEXT];
        }

        $queryBuilder = $this->entityManager->getRepository(ArticleDimensionContentInterface::class)
            ->createQueryBuilder('dimensionContent')
            ->select('DISTINCT dimensionContent.templateKey')
            ->where('dimensionContent.article = :id')
            ->andWhere('dimensionContent.stage = :stage')
            ->andWhere('dimensionContent.version = :version')
            ->setParameter('id', $articleId)
            ->setParameter('stage', DimensionContentInterface::STAGE_DRAFT)
            ->setParameter('version', DimensionContentInterface::CURRENT_VERSION);

        if ([] !== $locales) {
            $queryBuilder->andWhere('dimensionContent.locale IN (:locales)')->setParameter('locales', $locales);
        } else {
            // the unlocalized dimension holds no template
            $queryBuilder->andWhere('dimensionContent.locale IS NOT NULL');
        }

        $contexts = [];
        foreach ($queryBuilder->getQuery()->getSingleColumnResult() as $templateKey) {
            $contexts[] = $this->resolveInGroups($groups, \is_string($templateKey) ? $templateKey : null);
        }

        return \array_values(\array_unique($contexts));
    }

    /**
     * @param array<string, FormGroup> $groups
     */
    private function resolveInGroups(array $groups, ?string $templateKey): string
    {
        if (null !== $templateKey) {
            foreach ($groups as $group) {
                if (\in_array($templateKey, $group->templates, true)) {
                    return $this->resolveByGroup($group, $groups);
                }
            }
        }

        return ArticleAdmin::SECURITY_CONTEXT;
    }
}
