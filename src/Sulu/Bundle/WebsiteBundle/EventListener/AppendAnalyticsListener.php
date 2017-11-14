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
    private static $positions = [
        'head-open' => [
            'regex' => '/(<head [^>]*>|<head>)/',
            'sprintf' => '$1%s',
        ],
        'head-close' => [
            'regex' => '/<\/head>/',
            'sprintf' => '%s</head>',
        ],
        'body-open' => [
            'regex' => '/(<body [^>]*>|<body>)/',
            'sprintf' => '$1%s',
        ],
        'body-close' => [
            'regex' => '/<\/body>/',
            'sprintf' => '%s</body>',
        ],
    ];

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

        $analyticsArray = $this->analyticsRepository->findByUrl(
            $portalUrl,
            $this->requestAnalyzer->getPortalInformation()->getWebspaceKey(),
            $this->environment
        );

        $analyticsContent = [];
        foreach ($analyticsArray as $analytics) {
            $analyticsContent = $this->generateAnalyticsContent($analyticsContent, $analytics);
        }

        $response = $event->getResponse();
        $response->setContent($this->setAnalyticsContent($response->getContent(), $analyticsContent));
    }

    /**
     * Generate content for each possible position.
     *
     * @param array $analyticsContent
     * @param Analytics $analytics
     *
     * @return array
     */
    protected function generateAnalyticsContent(array $analyticsContent, Analytics $analytics)
    {
        foreach (array_keys(self::$positions) as $position) {
            $template = 'SuluWebsiteBundle:Analytics:' . $analytics->getType() . '/' . $position . '.html.twig';

            if (!$this->engine->exists($template)) {
                continue;
            }

            $content = $this->engine->render($template, ['analytics' => $analytics]);
            if (!$content) {
                continue;
            }

            if (!array_key_exists($position, $analyticsContent)) {
                $analyticsContent[$position] = '';
            }

            $analyticsContent[$position] .= $content;
        }

        return $analyticsContent;
    }

    /**
     * Set the generated content for each position.
     *
     * @param string $responseContent
     * @param array $analyticsContent
     *
     * @return string
     */
    protected function setAnalyticsContent($responseContent, array $analyticsContent)
    {
        foreach ($analyticsContent as $id => $content) {
            if (!$content) {
                continue;
            }

            $responseContent = preg_replace(
                self::$positions[$id]['regex'],
                sprintf(self::$positions[$id]['sprintf'], $content),
                $responseContent
            );
        }

        return $responseContent;
    }
}
