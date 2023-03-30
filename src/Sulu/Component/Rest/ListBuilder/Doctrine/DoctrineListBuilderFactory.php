<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Doctrine;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Component\Rest\ListBuilder\Filter\FilterTypeRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Factory for DoctrineListBuilders.
 */
class DoctrineListBuilderFactory implements DoctrineListBuilderFactoryInterface
{
    private \Doctrine\ORM\EntityManager $em;

    private \Sulu\Component\Rest\ListBuilder\Filter\FilterTypeRegistry $filterTypeRegistry;

    private \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher;

    private array $permissions;

    private \Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer $accessControlQueryEnhancer;

    public function __construct(
        EntityManager $em,
        FilterTypeRegistry $filterTypeRegistry,
        EventDispatcherInterface $eventDispatcher,
        array $permissions,
        AccessControlQueryEnhancer $accessControlQueryEnhancer
    ) {
        $this->em = $em;
        $this->filterTypeRegistry = $filterTypeRegistry;
        $this->eventDispatcher = $eventDispatcher;
        $this->permissions = $permissions;
        $this->accessControlQueryEnhancer = $accessControlQueryEnhancer;
    }

    /**
     * Creates a new DoctrineListBuilder for the given entity name and returns it.
     *
     * @param class-string $entityName
     *
     * @return DoctrineListBuilder
     */
    public function create($entityName)
    {
        return new DoctrineListBuilder(
            $this->em,
            $entityName,
            $this->filterTypeRegistry,
            $this->eventDispatcher,
            $this->permissions,
            $this->accessControlQueryEnhancer
        );
    }
}
