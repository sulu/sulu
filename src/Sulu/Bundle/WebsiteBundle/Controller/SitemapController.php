<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapXMLGeneratorInterface;
use Sulu\Component\Content\Repository\Mapping\MappingBuilder;
use Sulu\Component\HttpCache\HttpCache;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders a xml sitemap.
 */
class SitemapController extends WebsiteController
{
    /**
     * Returns a rendered xmlsitemap.
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        /** @var RequestAnalyzerInterface $requestAnalyzer */
        $requestAnalyzer = $this->get('sulu_core.webspace.request_analyzer');

        /** @var SitemapXMLGeneratorInterface $sitemapXMLGenerator */
        $sitemapXMLGenerator = $this->get('sulu_website.sitemap_xml_generator');

        $sitemap = $this->get('sulu_content.content_repository')->findAllByPortal(
            $requestAnalyzer->getPortal()->getXDefaultLocalization()->getLocalization(),
            $requestAnalyzer->getPortal()->getKey(),
            MappingBuilder::create()
                ->addProperties(['changed'])
                ->setResolveUrl(true)
                ->getMapping()
        );

        $webspaceSitemaps = [
            [
                'localizations' => array_map(
                    function (Localization $localization) {
                        return $localization->getLocalization();
                    },
                    $requestAnalyzer->getWebspace()->getAllLocalizations()
                ),
                'defaultLocalization' => $requestAnalyzer->getWebspace()->getXDefaultLocalization()->getLocalization(),
                'sitemap' => $sitemap,
            ],
        ];

        $preferredDomain = $request->getHttpHost();

        // XML Response
        $response = new Response();
        $response->setMaxAge(240);
        $response->setSharedMaxAge(960);

        $response->headers->set(
            HttpCache::HEADER_REVERSE_PROXY_TTL,
            $response->getAge() + $this->container->getParameter('sulu_website.sitemap.cache.lifetime')
        );

        $response->headers->set('Content-Type', 'text/xml');

        $response->setContent(
            $sitemapXMLGenerator->generate($webspaceSitemaps, $preferredDomain, $request->getScheme())
        );

        // Generate XML
        return $response;
    }
}
