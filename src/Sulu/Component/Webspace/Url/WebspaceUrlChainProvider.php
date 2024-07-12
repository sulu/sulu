<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Url;

use Sulu\Component\Webspace\Webspace;

/**
 * Combines multiple url-provider.
 */
class WebspaceUrlChainProvider implements WebspaceUrlProviderInterface
{
    /**
     * @var iterable<WebspaceUrlProviderInterface>
     */
    private $chain;

    /**
     * @param iterable<WebspaceUrlProviderInterface> $chain
     */
    public function __construct(iterable $chain = [])
    {
        $this->chain = $chain;
    }

    public function getUrls(Webspace $webspace, $environment)
    {
        $urls = [];
        foreach ($this->chain as $provider) {
            $urls = \array_merge($urls, $provider->getUrls($webspace, $environment));
        }

        return $urls;
    }
}
