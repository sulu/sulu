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

namespace Sulu\Component\Rest\Exception;

interface RestExceptionInterface extends \Throwable
{
    /**
     * @see UniqueConstraintViolationException
     *
     * @var int
     */
    const EXCEPTION_CODE_UNIQUE_CONSTRAINT_VIOLATION = 1101;

    /**
     * @see InvalidHashException
     *
     * @var int
     */
    const EXCEPTION_CODE_INVALID_HASH = 1102;

    /**
     * Cannot delete resource, because it has children.
     *
     * @see DeletionWithChildrenNotAllowedExceptionInterface
     *
     * @var int
     */
    const EXCEPTION_CODE_DELETION_WITH_CHILDREN_NOT_ALLOWED = 1103;

    /**
     * Cannot delete resource, because the user has insufficient permissions for some of it's children.
     *
     * @see InsufficientChildPermissionsExceptionInterface
     *
     * @var int
     */
    const EXCEPTION_CODE_INSUFFICIENT_CHILD_PERMISSIONS = 1104;
}
