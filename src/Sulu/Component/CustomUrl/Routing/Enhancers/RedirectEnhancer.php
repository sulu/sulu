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
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

/**
 * If custom-url is a redirect it appends url to defaults.
 */
class RedirectEnhancer extends AbstractEnhancer
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    public function __construct(WebspaceManagerInterface $webspaceManager)
    {
        $this->webspaceManager = $webspaceManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function doEnhance(
        CustomUrlBehavior $customUrl,
        Webspace $webspace,
        array $defaults,
        Request $request
    ) {
        $resourceSegment = '/';
        if ($customUrl->getTargetDocument() !== null) {
            $resourceSegment = $customUrl->getTargetDocument()->getResourceSegment();
        }

        $url = $this->webspaceManager->findUrlByResourceLocator(
            $resourceSegment,
            $defaults['_environment'],
            $customUrl->getTargetLocale(),
            $defaults['_webspace']->getKey()
        );

        return [
            '_controller' => 'SuluWebsiteBundle:Redirect:redirect',
            'url' => $url,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function supports(CustomUrlBehavior $customUrl)
    {
        return $customUrl->isRedirect() || $customUrl->getTargetDocument() === null;
    }
}
