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

use Sulu\Bundle\WebsiteBundle\Entity\Analytics;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsRepository;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Templating\EngineInterface;

/**
 * Appends analytics scripts into body.
 */
class AppendAnalyticsListener
{
    const POSITION_HEAD_OPEN = 'head-open';
    const POSITION_HEAD_CLOSE = 'head-close';
    const POSITION_BODY_OPEN = 'body-open';
    const POSITION_BODY_CLOSE = 'body-close';

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

    /**
     * @var bool
     */
    private $preview;

    /**
     * @param EngineInterface $engine
     * @param RequestAnalyzerInterface $requestAnalyzer
     * @param AnalyticsRepository $analyticsRepository
     * @param $environment
     * @param bool $preview
     */
    public function __construct(
        EngineInterface $engine,
        RequestAnalyzerInterface $requestAnalyzer,
        AnalyticsRepository $analyticsRepository,
        $environment,
        $preview = false
    ) {
        $this->engine = $engine;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->analyticsRepository = $analyticsRepository;
        $this->environment = $environment;
        $this->preview = $preview;
    }

    /**
     * Appends analytics scripts into body.
     *
     * @param FilterResponseEvent $event
     */
    public function onResponse(FilterResponseEvent $event)
    {
        if ($this->preview
            || 0 !== strpos($event->getResponse()->headers->get('Content-Type'), 'text/html')
            || null === $this->requestAnalyzer->getPortalInformation()
        ) {
            return;
        }

        $portalUrl = $this->requestAnalyzer->getAttribute('urlExpression');
        /** @var Analytics[] $analyticsArray */
        $analyticsArray = $this->analyticsRepository->findByUrl(
            $portalUrl,
            $this->requestAnalyzer->getPortalInformation()->getWebspaceKey(),
            $this->environment
        );

        $analyticsContent = [
            self::POSITION_HEAD_OPEN => null,
            self::POSITION_HEAD_CLOSE => null,
            self::POSITION_BODY_OPEN => null,
            self::POSITION_BODY_CLOSE => null,
        ];
        foreach ($analyticsArray as $analytics) {
            $type = $analytics->getType();
            foreach ($analyticsContent as $tag => &$value) {
                $template = 'SuluWebsiteBundle:Analytics:' . $type . DIRECTORY_SEPARATOR . $tag . '.html.twig';

                if (!$this->engine->exists($template)) {
                    continue;
                }

                $value .= $this->engine->render($template, ['analytics' => $analytics]);
            }
        }

        $response = $event->getResponse();
        $content = $response->getContent();

        if ($analyticsContent[self::POSITION_HEAD_OPEN]) {
            $matches = [];
            preg_match('/<head[^>]*>/', $content, $matches);
            $content = str_replace($matches[0],  $matches[0] . $analyticsContent[self::POSITION_HEAD_OPEN], $content);
        }

        if ($analyticsContent[self::POSITION_HEAD_CLOSE]) {
            $content = str_replace('</head>', $analyticsContent[self::POSITION_HEAD_CLOSE] . '</head>', $content);
        }

        if ($analyticsContent[self::POSITION_BODY_OPEN]) {
            $matches = [];
            preg_match('/<body[^>]*>/', $content, $matches);
            $content = str_replace($matches[0], $matches[0] . $analyticsContent[self::POSITION_BODY_OPEN], $content);
        }

        if ($analyticsContent[self::POSITION_BODY_CLOSE]) {
            $content = str_replace('</body>', $analyticsContent[self::POSITION_BODY_CLOSE] . '</body>', $content);
        }

        $response->setContent($content);
    }
}
