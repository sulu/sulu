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

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancerInterface;
use Sulu\Component\Rest\ListBuilder\Filter\FilterTypeRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Factory for DoctrineListBuilders.
 */
class DoctrineListBuilderFactory implements DoctrineListBuilderFactoryInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private FilterTypeRegistry $filterTypeRegistry,
        private EventDispatcherInterface $eventDispatcher,
        private array $permissions,
        private AccessControlQueryEnhancerInterface $accessControlQueryEnhancer,
    ) {
    }

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
