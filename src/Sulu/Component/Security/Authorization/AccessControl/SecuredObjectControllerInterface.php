<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization\AccessControl;

use Symfony\Component\HttpFoundation\Request;

/**
 * This interface has to be implemented by controller, which want to check security on per-object basis.
 */
interface SecuredObjectControllerInterface
{
    /**
     * Returns the class name of the object to check.
     *
     * @return string
     */
    public function getSecuredClass();

    /**
     * Returns the id of the object to check.
     *
     * @param Request $request
     *
     * @return string
     */
    public function getSecuredObjectId(Request $request);

    /**
     * Returns the locale for the given request.
     *
     * @param Request $request
     *
     * @return string
     */
    public function getLocale(Request $request);
}
