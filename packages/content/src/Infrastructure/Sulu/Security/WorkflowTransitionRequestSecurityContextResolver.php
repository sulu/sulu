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
use Sulu\Content\Domain\Exception\UnresolvableSecurityContextException;

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
            throw new UnresolvableSecurityContextException(\sprintf(
                'No security context provider is registered for resource key "%s", so a workflow transition on it'
                . ' cannot be authorized. Tag a service with "%s" and resource-key "%s". Known keys: %s.',
                $resourceKey,
                'sulu_content.workflow_transition_request_security_context_provider',
                $resourceKey,
                [] === $this->providers ? 'none' : \implode(', ', \array_keys($this->providers)),
            ));
        }

        return $this->providers[$resourceKey]->resolve($resourceId, $locale);
    }
}
