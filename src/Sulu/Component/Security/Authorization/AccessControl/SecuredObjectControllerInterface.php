<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization\AccessControl;

/**
 * This interface has to be implemented by controller, which want to check security on per-object basis.
 */
interface SecuredObjectControllerInterface
{
    /**
     * Returns the class name of the object to check
     * @return string
     */
    public function getSecuredClass();
}
