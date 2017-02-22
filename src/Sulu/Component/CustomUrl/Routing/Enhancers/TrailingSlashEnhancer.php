<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Routing\Enhancers;

use Sulu\Component\CustomUrl\Document\CustomUrlBehavior;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

/**
 * Redirects to a non trailing slash uri.
 */
class TrailingSlashEnhancer extends AbstractEnhancer
{
    /**
     * {@inheritdoc}
     */
    protected function doEnhance(
        CustomUrlBehavior $customUrl,
        Webspace $webspace,
        array $defaults,
        Request $request
    ) {
        if ($request->getRequestUri() === '/' || substr($request->getRequestUri(), -1, 1) !== '/') {
            return [];
        }

        return [
            '_finalized' => true,
            '_controller' => 'SuluWebsiteBundle:Redirect:redirect',
            'url' => substr($request->getUri(), 0, -1),
        ];
    }
}
