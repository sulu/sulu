<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Sitemap;

/**
 * Generate the Sitemap XML based on one or several WebspaceSitemaps.
 */
class SitemapXMLGenerator implements SitemapXMLGeneratorInterface
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var string
     */
    private $renderFile;

    public function __construct(
        \Twig_Environment $twig,
        $renderFile = 'SuluWebsiteBundle:Sitemap:sitemap.xml.twig'
    ) {
        $this->twig = $twig;
        $this->renderFile = $renderFile;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($webspaceSitemaps, $domain = null, $scheme = 'http', $renderFile = null)
    {
        $renderFile = $renderFile ?: $this->renderFile;

        return $this->twig->render(
            $renderFile,
            [
                'webspaceSitemaps' => $webspaceSitemaps,
                'domain' => $domain,
                'scheme' => $scheme,
            ]
        );
    }
}
