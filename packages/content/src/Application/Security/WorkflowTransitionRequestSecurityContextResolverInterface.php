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

namespace Sulu\Content\Application\Security;

use Sulu\Component\Security\Authorization\SecurityCondition;

/**
 * @internal this interface is internal and should not be implemented or used in another context
 */
interface WorkflowTransitionRequestSecurityContextResolverInterface
{
    /**
     * Resolves the security condition for the given resource: context, locale, and the object
     * identity where the resource has per-object access control.
     *
     * @throws \RuntimeException if no provider is registered for the resource key
     */
    public function resolve(string $resourceKey, string $resourceId, string $locale): SecurityCondition;
}
