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

use Sulu\Content\Domain\Exception\WorkflowTransitionRequestCancelNotAllowedException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @internal this interface is internal and should not be implemented or used in another context
 */
interface WorkflowTransitionAdminAuthorizerInterface
{
    /**
     * What the current user may do with one request, answered by the same rules the assertions
     * enforce. The admin renders its buttons from this, because content without object security
     * carries no permissions the frontend could read.
     *
     * @return array{cancel: bool, publish: bool, retry: bool, review: bool}
     */
    public function getPermissions(string $resourceKey, string $resourceId, string $locale): array;

    /**
     * @throws AccessDeniedException when the user holds neither the LIVE permission nor the EDIT
     *                               permission together with an approved active request
     */
    public function assertCanPublish(string $resourceKey, string $resourceId, string $locale): void;

    /**
     * Covers every reviewer verdict: the content-level `reject` transition as well as approving or
     * rejecting a request on the message bus.
     *
     * @throws AccessDeniedException when the user lacks the REVIEW permission for the resolved context
     */
    public function assertCanReview(string $resourceKey, string $resourceId, string $locale): void;

    /**
     * @throws WorkflowTransitionRequestCancelNotAllowedException when an active request covers the
     *                                                            content and the user lacks EDIT
     */
    public function assertCanCancelReview(string $resourceKey, string $resourceId, string $locale): void;
}
