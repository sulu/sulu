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

use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Admin\ArticleAdmin;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Content\Application\Security\WorkflowTransitionRequestSecurityContextResolverInterface;

/**
 * `sulu_admin.resources` declares the base context only, so the group context is resolved here.
 *
 * @internal
 */
final class ArticleWorkflowTransitionRequestSecurityContextResolver implements WorkflowTransitionRequestSecurityContextResolverInterface
{
    public function __construct(
        private readonly WorkflowTransitionRequestSecurityContextResolverInterface $inner,
        private readonly ArticleSecurityContextResolver $articleSecurityContextResolver,
    ) {
    }

    public function resolve(string $resourceKey, string $resourceId, string $locale): SecurityCondition
    {
        if (ArticleInterface::RESOURCE_KEY === $resourceKey) {
            $securityContext = $this->articleSecurityContextResolver->resolveByArticle($resourceId, [$locale])[0] ?? null;

            if (null !== $securityContext && ArticleAdmin::SECURITY_CONTEXT !== $securityContext) {
                return new SecurityCondition($securityContext, $locale);
            }
        }

        return $this->inner->resolve($resourceKey, $resourceId, $locale);
    }

    public function has(string $resourceKey): bool
    {
        return $this->inner->has($resourceKey);
    }
}
