<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Admin\Helper;

use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class UrlSelect
{
    /**
     * @var string
     */
    protected $environment;

    public function __construct(private WebspaceManagerInterface $webspaceManager, string $environment)
    {
        $this->environment = $environment;
    }

    public function getValues(): array
    {
        return \array_map(
            function(string $url) {
                return [
                    'name' => $url,
                    'title' => $url,
                ];
            },
            $this->webspaceManager->getUrls($this->environment)
        );
    }
}
