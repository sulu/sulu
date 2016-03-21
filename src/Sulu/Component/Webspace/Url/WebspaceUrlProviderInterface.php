<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Url;

use Sulu\Component\Webspace\Url;
use Sulu\Component\Webspace\Webspace;

/**
 * Provides urls for given webspace.
 */
interface WebspaceUrlProviderInterface
{
    /**
     * Returns urls for given webspace.
     *
     * @param Webspace $webspace
     * @param string $environment
     *
     * @return Url[]
     */
    public function getUrls(Webspace $webspace, $environment);
}
