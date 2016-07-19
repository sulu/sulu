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
 * Append seo information to route.
 */
class SeoEnhancer extends AbstractEnhancer
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
        $seo = [
            'noFollow' => $customUrl->isNoFollow(),
            'noIndex' => $customUrl->isNoIndex(),
        ];

        if ($customUrl->isCanonical()) {
            $resourceSegment = $customUrl->getTargetDocument()->getResourceSegment();
            $seo['canonicalUrl'] = $this->webspaceManager->findUrlByResourceLocator(
                $resourceSegment,
                $defaults['_environment'],
                $customUrl->getTargetLocale(),
                $webspace->getKey()
            );
        }

        return ['_seo' => $seo];
    }

    /**
     * {@inheritdoc}
     */
    protected function supports(CustomUrlBehavior $customUrl)
    {
        return $customUrl->getTargetDocument() !== null;
    }
}
