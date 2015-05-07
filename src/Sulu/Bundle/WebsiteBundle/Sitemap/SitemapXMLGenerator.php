<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Sitemap;

/**
 * Class SitemapXMLGenerator
 * Generate the Sitemap XML based on one or several WebspaceSitemaps
 */
class SitemapXMLGenerator implements SitemapXMLGeneratorInterface
{
    /**
     * @var string
     */
    private $twig;

    /**
     * @var string
     */
    private $renderFile;

    public function __construct(
        $twig,
        $renderFile = 'SuluWebsiteBundle:Sitemap:sitemap.xml.twig'
    ) {
        $this->twig = $twig;
        $this->renderFile = $renderFile;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($webspaceSitemaps, $domain = null, $renderFile = null)
    {
        $renderFile = $renderFile ? : $this->renderFile;

        return $this->twig->render(
            $renderFile,
            array(
                'webspaceSitemapInformations' => $webspaceSitemaps,
                'domain'                      => $domain
            )
        );
    }
}
