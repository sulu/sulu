<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security;

/**
 * Controllers implementing this interface security will be automatically applied
 * @package Sulu\Component\Security
 */
interface SecuredControllerInterface
{
    /**
     * Returns the SecurityContext required for the controller
     * @return mixed
     */
    public function getSecurityContext();
} 
