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

use Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException;

interface RestExceptionInterface extends \Throwable
{
    /**
     * @see UniqueConstraintViolationException
     *
     * @var int
     */
    public const EXCEPTION_CODE_UNIQUE_CONSTRAINT_VIOLATION = 1101;

    /**
     * @see InvalidHashException
     *
     * @var int
     */
    public const EXCEPTION_CODE_INVALID_HASH = 1102;

    /**
     * @see ResourceLocatorAlreadyExistsException
     *
     * @var int
     */
    public const EXCEPTION_CODE_RESOURCE_LOCATOR_ALREADY_EXISTS = 1103;

    /**
     * @see InsufficientDescendantPermissionsException
     *
     * @var int
     */
    public const EXCEPTION_CODE_INSUFFICIENT_DESCENDANT_PERMISSIONS = 1104;

    /**
     * @see RemoveDependantResourcesFoundExceptionInterface
     *
     * @var int
     */
    public const EXCEPTION_CODE_DEPENDANT_RESOURCES_FOUND = 1105;

    /**
     * @see ReferencingResourcesFoundExceptionInterface
     *
     * @var int
     */
    public const EXCEPTION_CODE_REFERENCING_RESOURCES_FOUND = 1106;
}
