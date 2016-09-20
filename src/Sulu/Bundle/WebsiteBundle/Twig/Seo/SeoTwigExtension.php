<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Seo;

use Sulu\Bundle\WebsiteBundle\Twig\Content\ContentPathInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * This twig extension provides support for the SEO functionality provided by Sulu.
 */
class SeoTwigExtension extends \Twig_Extension
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var ContentPathInterface
     */
    private $contentPath;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        RequestAnalyzerInterface $requestAnalyzer,
        ContentPathInterface $contentPath,
        RequestStack $requestStack
    ) {
        $this->requestAnalyzer = $requestAnalyzer;
        $this->requestStack = $requestStack;
        // FIXME Should not use another twig extension here, that is not the intended use case of twig extensions
        $this->contentPath = $contentPath;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_seo';
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_seo', [$this, 'renderSeoTags'], ['needs_environment' => true]),
        ];
    }

    /**
     * Renders the correct title of the current page. The correct title is either the title provided by the SEO
     * extension, or the title of the content, if the SEO extension does not provide one.
     *
     * @deprecated use the twig include function to render the seo
     */
    public function renderSeoTags(
        \Twig_Environment $twig,
        array $seoExtension,
        array $content,
        array $urls,
        $shadowBaseLocale
    ) {
        $template = 'SuluWebsiteBundle:Extension:seo.html.twig';

        @trigger_error(
            'This twig extension is deprecated and should not be used anymore, include the "%s".',
            $template
        );

        $defaultLocale = null;
        $portal = $this->requestAnalyzer->getPortal();
        if ($portal) {
            $defaultLocale = $portal->getXDefaultLocalization()->getLocale();
        }

        return $twig->render(
            $template,
            [
                'seo' => $seoExtension,
                'content' => $content,
                'urls' => $urls,
                'defaultLocale' => $defaultLocale,
                'shadowBaseLocale' => $shadowBaseLocale,
            ]
        );
    }
}
