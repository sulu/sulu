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

namespace Sulu\Content\Infrastructure\Sulu\Security;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Component\Security\Authorization\AccessControl\SecuredEntityInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Content\Application\Security\WorkflowTransitionRequestSecurityContextResolverInterface;
use Sulu\Content\Domain\Exception\UnresolvableSecurityContextException;

/**
 * Resolves the security condition from `sulu_admin.resources`, the same declaration the permission
 * tab reads, so a resource joins the request workflow without a service of its own.
 *
 * @internal
 */
final class WorkflowTransitionRequestSecurityContextResolver implements WorkflowTransitionRequestSecurityContextResolverInterface
{
    /**
     * @param array<string, array{security_context?: string, security_class?: class-string}> $resources
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly array $resources,
    ) {
    }

    public function resolve(string $resourceKey, string $resourceId, string $locale): SecurityCondition
    {
        $securityContext = $this->resources[$resourceKey]['security_context'] ?? null;

        if (!\is_string($securityContext)) {
            throw new UnresolvableSecurityContextException(\sprintf(
                'Resource "%s" declares no "security_context", so a workflow transition on it cannot be'
                . ' authorized. Add it to "sulu_admin.resources.%s". Resources declaring one: %s.',
                $resourceKey,
                $resourceKey,
                \implode(', ', $this->securedResourceKeys()) ?: 'none',
            ));
        }

        $securityClass = $this->resources[$resourceKey]['security_class'] ?? null;

        if (!\is_string($securityClass) || !\is_subclass_of($securityClass, SecuredEntityInterface::class)) {
            return new SecurityCondition($securityContext, $locale);
        }

        // The declared context of such a resource carries a placeholder, `sulu.webspaces.#webspace#`
        // for pages, which only the entity can fill; it also has per-object access control.
        $entity = $this->entityManager->find($securityClass, $resourceId);

        if (!$entity instanceof SecuredEntityInterface) {
            throw new UnresolvableSecurityContextException(\sprintf(
                'Cannot resolve a security context from "%s" with id "%s": it does not exist.',
                $securityClass,
                $resourceId,
            ));
        }

        return new SecurityCondition($entity->getSecurityContext(), $locale, $securityClass, $resourceId);
    }

    public function has(string $resourceKey): bool
    {
        return \is_string($this->resources[$resourceKey]['security_context'] ?? null);
    }

    /**
     * @return list<string>
     */
    private function securedResourceKeys(): array
    {
        $resourceKeys = [];

        foreach ($this->resources as $resourceKey => $resource) {
            if (\is_string($resource['security_context'] ?? null)) {
                $resourceKeys[] = $resourceKey;
            }
        }

        return $resourceKeys;
    }
}
