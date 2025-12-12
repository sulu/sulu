<?php

declare(strict_types=1);

namespace Sulu\Bundle\SecurityBundle\AccessControl;

use Doctrine\ORM\QueryBuilder;
use Sulu\Component\Security\Authentication\UserInterface;

interface AccessControlQueryEnhancerInterface
{
    /**
     * @param class-string $entityClass
     */
    public function enhance(QueryBuilder $queryBuilder, ?UserInterface $user, int $permission, string $entityClass, string $entityAlias): void;

    /**
     * @param class-string $entityClass
     */
    public function enhanceWithDynamicEntityClass(QueryBuilder $queryBuilder, ?UserInterface $user, int $permission, string $entityClass, string $entityAlias, string $entityClassField, string $entityIdField): void;
}
