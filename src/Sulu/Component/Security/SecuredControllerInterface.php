<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security;

use Symfony\Component\HttpFoundation\Request;

/**
 * Controllers implementing this interface security will be automatically applied.
 */
interface SecuredControllerInterface
{
    /**
     * Returns the SecurityContext required for the controller.
     *
     * @return mixed
     */
    public function getSecurityContext();

    /**
     * Returns the locale for the given request.
     *
     * @param Request $request
     *
     * @return string
     */
    public function getLocale(Request $request);
}
