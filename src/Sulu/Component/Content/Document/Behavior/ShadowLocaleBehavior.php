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
 * The implemting document will be to enable a "shadow locale". Shadow locale
 * (a.k.a fallback locale) means that translations will be loaded from the
 * assigned shadow locale instead of the documents assigned locale.
 */
interface ShadowLocaleBehavior
{
    /**
     * Return the activation state of the shadow locale feature.
     *
     * @return bool
     */
    public function isShadowLocaleEnabled();

    /**
     * Enable or disable the shadow locale.
     *
     * @param bool
     */
    public function setShadowLocaleEnabled($shadowLocaleEnabled);

    /**
     * Return the shadow locale.
     *
     * @return string
     */
    public function getShadowLocale();

    /**
     * Set the shadow locale.
     *
     * @param string
     */
    public function setShadowLocale($shadowLocale);
}
