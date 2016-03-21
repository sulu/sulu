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

use Sulu\Component\Webspace\Webspace;

/**
 * Returns urls of given webspace.
 */
class WebspaceUrlProvider implements WebspaceUrlProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getUrls(Webspace $webspace, $environment)
    {
        $urls = [];
        foreach ($webspace->getPortals() as $portal) {
            $urls = array_merge($urls, $portal->getEnvironment($environment)->getUrls());
        }

        return $urls;
    }
}
