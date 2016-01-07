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

use Sulu\Bundle\WebsiteBundle\Entity\AnalyticRepository;
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
     * @var AnalyticRepository
     */
    private $analyticRepository;

    /**
     * @var string
     */
    private $environment;

    public function __construct(
        EngineInterface $engine,
        RequestAnalyzerInterface $requestAnalyzer,
        AnalyticRepository $analyticRepository,
        $environment
    ) {
        $this->engine = $engine;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->analyticRepository = $analyticRepository;
        $this->environment = $environment;
    }

    /**
     * Appends analytics scripts into body.
     *
     * @param FilterResponseEvent $event
     */
    public function onResponse(FilterResponseEvent $event)
    {
        if ($event->getRequest()->getRequestFormat() !== 'html') {
            return;
        }

        $analytics = $this->analyticRepository->findByUrl(
            $this->requestAnalyzer->getPortalInformation()->getUrlExpression(),
            $this->environment
        );

        $content = $this->engine->render('SuluWebsiteBundle:Analytics:render.html.twig', ['analytics' => $analytics]);

        $response = $event->getResponse();
        $responseContent = $response->getContent();
        $response->setContent(str_replace('</body>', $content . '</body>', $responseContent));
    }
}
