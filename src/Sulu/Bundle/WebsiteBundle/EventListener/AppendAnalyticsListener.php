<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\EventListener;

use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsRepository;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Templating\EngineInterface;

/**
 * Appends analytics scripts into body.
 */
class AppendAnalyticsListener
{
    /**
     * @var EngineInterface
     */
    private $engine;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var AnalyticsRepository
     */
    private $analyticsRepository;

    /**
     * @var string
     */
    private $environment;

    public function __construct(
        EngineInterface $engine,
        RequestAnalyzerInterface $requestAnalyzer,
        AnalyticsRepository $analyticsRepository,
        $environment
    ) {
        $this->engine = $engine;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->analyticsRepository = $analyticsRepository;
        $this->environment = $environment;
    }

    /**
     * Appends analytics scripts into body.
     *
     * @param FilterResponseEvent $event
     */
    public function onResponse(FilterResponseEvent $event)
    {
        if (0 !== strpos($event->getResponse()->headers->get('Content-Type'), 'text/html')
            || $this->requestAnalyzer->getPortalInformation() === null
        ) {
            return;
        }

        $portalUrl = $this->requestAnalyzer->getAttribute('urlExpression');
        $analytics = $this->analyticsRepository->findByUrl(
            $portalUrl,
            $this->requestAnalyzer->getPortalInformation()->getWebspaceKey(),
            $this->environment
        );

        $content = $this->engine->render('SuluWebsiteBundle:Analytics:website.html.twig', ['analytics' => $analytics]);

        $response = $event->getResponse();
        $responseContent = $response->getContent();
        $response->setContent(str_replace('</body>', $content . '</body>', $responseContent));
    }
}
