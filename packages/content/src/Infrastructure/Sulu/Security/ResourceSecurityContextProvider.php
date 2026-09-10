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
use Sulu\Content\Application\Security\WorkflowTransitionRequestSecurityContextProviderInterface;

/**
 * Ready-made provider for resources whose context is fixed or carried by the entity. Register one
 * per resource key from the bundle that owns the resource.
 */
final class ResourceSecurityContextProvider implements WorkflowTransitionRequestSecurityContextProviderInterface
{
    /**
     * @param class-string $entityClass
     * @param string|null $securityContext the fixed context of the resource, null for entities carrying their own
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $entityClass,
        private readonly ?string $securityContext = null,
    ) {
    }

    public function resolve(string $resourceId, string $locale): SecurityCondition
    {
        if (null !== $this->securityContext) {
            return new SecurityCondition($this->securityContext, $locale);
        }

        $entity = $this->entityManager->find($this->entityClass, $resourceId);

        if (!$entity instanceof SecuredEntityInterface) {
            throw new \RuntimeException(\sprintf(
                'Cannot resolve a security context from "%s" with id "%s".',
                $this->entityClass,
                $resourceId,
            ));
        }

        // Entities like pages carry their context per instance and have per-object access control,
        // so the class and id go along with it.
        return new SecurityCondition($entity->getSecurityContext(), $locale, $entity::class, $resourceId);
    }
}
