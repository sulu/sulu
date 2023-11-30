<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\EventListener;

use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsInterface;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsRepositoryInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Twig\Environment;

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
     * @var Environment
     */
    private $engine;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var AnalyticsRepositoryInterface
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

    public function __construct(
        Environment $engine,
        RequestAnalyzerInterface $requestAnalyzer,
        AnalyticsRepositoryInterface $analyticsRepository,
        $environment,
        bool $preview = false
    ) {
        $this->engine = $engine;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->analyticsRepository = $analyticsRepository;
        $this->environment = $environment;
        $this->preview = $preview;
    }

    /**
     * Appends analytics scripts into body.
     */
    public function onResponse(ResponseEvent $event)
    {
        if (!$event->isMainRequest()
            || $this->preview
        ) {
            return;
        }

        $response = $event->getResponse();

        if (0 !== \strpos($response->headers->get('Content-Type', ''), 'text/html')
            || !$response->getContent()
            || null === $this->requestAnalyzer->getPortalInformation()
        ) {
            return;
        }

        $portalUrl = $this->requestAnalyzer->getAttribute('urlExpression');

        if (!$portalUrl) {
            return;
        }

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
     * @return array
     */
    protected function generateAnalyticsContent(array $analyticsContent, AnalyticsInterface $analytics)
    {
        foreach (\array_keys(self::$positions) as $position) {
            $template = '@SuluWebsite/Analytics/' . $analytics->getType() . '/' . $position . '.html.twig';

            if (!$this->engine->getLoader()->exists($template)) {
                continue;
            }

            $content = $this->engine->render($template, ['analytics' => $analytics]);
            if (!$content) {
                continue;
            }

            if (!\array_key_exists($position, $analyticsContent)) {
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
     *
     * @return string
     */
    protected function setAnalyticsContent($responseContent, array $analyticsContent)
    {
        foreach ($analyticsContent as $id => $content) {
            if (!$content) {
                continue;
            }

            $responseContent = \preg_replace(
                self::$positions[$id]['regex'],
                \sprintf(self::$positions[$id]['sprintf'], $content),
                $responseContent
            );
        }

        return $responseContent;
    }
}
