<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\EventListener;

use Sulu\Component\CustomUrl\Document\CustomUrlBehavior;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Appends canonical link head.
 */
class AppendCanonicalListener
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
     * Appends analytics scripts into body.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $attributes = $request->attributes->all();

        if ($request->getRequestFormat() !== 'html'
            || !array_key_exists('_custom_url', $attributes)
            || !$attributes['_custom_url']->isCanonical()
        ) {
            return;
        }

        /** @var CustomUrlBehavior $customUrl */
        $customUrl = $event->getRequest()->get('_custom_url');

        $resourceSegment = $customUrl->getTarget()->getResourceSegment();
        $url = $this->webspaceManager->findUrlByResourceLocator(
            $resourceSegment,
            $event->getRequest()->get('_environment'),
            $customUrl->getTargetLocale(),
            $event->getRequest()->get('_webspace')->getKey()
        );

        $content = sprintf('<link rel="canonical" href="%s">', $url);

        $response = $event->getResponse();
        $responseContent = $response->getContent();
        $response->setContent(str_replace('</head>', $content . '</head>', $responseContent));
    }
}
