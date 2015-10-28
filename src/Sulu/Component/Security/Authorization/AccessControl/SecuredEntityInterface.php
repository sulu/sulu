<?php
/*
 * This file is part of Sulu
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization\AccessControl;

/**
 * Interface for entities which can be protected by access control list.
 */
interface SecuredEntityInterface
{
    /**
     * Returns the unique id of the entity.
     *
     * @return int
     */
    public function getId();

    /**
     * Returns the security context, to which this class of entity is attached.
     *
     * @return mixed
     */
    public function getSecurityContext();
}
