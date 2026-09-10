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

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @internal this interface is internal and should not be implemented or used in another context
 */
interface WorkflowTransitionAuthorizerInterface
{
    /**
     * @throws AccessDeniedException when the user holds neither the LIVE permission nor the EDIT
     *                               permission together with an approved active request
     */
    public function assertCanPublish(string $resourceKey, string $resourceId, string $locale): void;

    /**
     * @throws AccessDeniedException when the user lacks the REVIEW permission for the resolved context
     */
    public function assertCanReject(string $resourceKey, string $resourceId, string $locale): void;
}
