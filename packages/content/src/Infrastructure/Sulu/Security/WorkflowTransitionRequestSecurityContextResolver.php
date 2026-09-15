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

use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Content\Application\Security\WorkflowTransitionRequestSecurityContextProviderInterface;
use Sulu\Content\Application\Security\WorkflowTransitionRequestSecurityContextResolverInterface;

/**
 * @internal
 */
final class WorkflowTransitionRequestSecurityContextResolver implements WorkflowTransitionRequestSecurityContextResolverInterface
{
    /**
     * @var array<string, WorkflowTransitionRequestSecurityContextProviderInterface>
     */
    private array $providers;

    /**
     * @param iterable<string, WorkflowTransitionRequestSecurityContextProviderInterface> $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = [...$providers];
    }

    public function resolve(string $resourceKey, string $resourceId, string $locale): SecurityCondition
    {
        if (!isset($this->providers[$resourceKey])) {
            throw new \RuntimeException(\sprintf(
                'No security context provider registered for resource key "%s", known keys: %s.',
                $resourceKey,
                \implode(', ', \array_keys($this->providers)),
            ));
        }

        return $this->providers[$resourceKey]->resolve($resourceId, $locale);
    }
}
