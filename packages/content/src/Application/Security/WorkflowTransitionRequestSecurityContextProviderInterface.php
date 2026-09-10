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
 * Resolves the security condition of one resource key. Register an implementation per resource key,
 * tagged `sulu_content.workflow_transition_request_security_context_provider` with a `resource-key`.
 */
interface WorkflowTransitionRequestSecurityContextProviderInterface
{
    public function resolve(string $resourceId, string $locale): SecurityCondition;
}
