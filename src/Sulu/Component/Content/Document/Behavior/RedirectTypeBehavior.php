<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Behavior;

/**
 * Classes implementing this behavior will be able to act as
 * redirects to external URLs or internal content.
 */
interface RedirectTypeBehavior
{
    /**
     * Return the redirect type.
     *
     * @return string
     */
    public function getRedirectType();

    /**
     * Set the redirect type.
     *
     * @param string $redirectType
     */
    public function setRedirectType($redirectType);

    /**
     * Return the internal redirect target document.
     *
     * Applies when the redirect type is RedirectType::INTERNAL
     *
     * @return object $document
     */
    public function getRedirectTarget();

    /**
     * Set the routable document to which the target shuld.
     *
     * Applies when the redirect type is RedirectType::INTERNAL
     *
     * @param object $redirectTarget
     */
    public function setRedirectTarget($redirectTarget);

    /**
     * Return the external redirect URL.
     *
     * Applies when the redirect type is RedirectType::EXTERNAL
     *
     * @return string
     */
    public function getRedirectExternal();

    /**
     * Set the external redirect URL.
     *
     * Applies when the redirect type is RedirectType::EXTERNAL
     *
     * @param string
     */
    public function setRedirectExternal($redirectExternal);
}
