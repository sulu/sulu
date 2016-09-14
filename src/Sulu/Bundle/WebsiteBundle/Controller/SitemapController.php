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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders a xml sitemap.
 */
class SitemapController extends WebsiteController
{
    /**
     * Render sitemap-index of all available sitemap.xml files.
     * If only one provider exists this provider will be rendered directly.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $pool = $this->get('sulu_website.sitemap.pool');
        if (!$pool->hasIndex()) {
            return $this->sitemapAction($request, $pool->getFirstAlias());
        }

        return $this->render('SuluWebsiteBundle:Sitemap:sitemap-index.xml.twig', ['sitemaps' => $pool->getIndex()]);
    }

    /**
     * Render sitemap.xml for a single provider.
     * If this provider has multiple-pages a sitemapindex will be rendered.
     *
     * @param Request $request
     * @param string $alias
     *
     * @return Response
     */
    public function sitemapAction(Request $request, $alias)
    {
        $provider = $this->get('sulu_website.sitemap.pool')->getProvider($alias);

        if (1 === ($maxPage = (int) $provider->getMaxPage())) {
            return $this->sitemapPaginatedAction($request, $alias, 1);
        }

        return $this->render(
            'SuluWebsiteBundle:Sitemap:sitemap-paginated-index.xml.twig',
            ['alias' => $alias, 'maxPage' => $maxPage]
        );
    }

    /**
     * Render a single page for a single sitemap.xml provider.
     *
     * @param Request $request
     * @param string $alias
     * @param int $page
     *
     * @return Response
     */
    public function sitemapPaginatedAction(Request $request, $alias, $page)
    {
        $portal = $request->get('_sulu')->getAttribute('portal');
        $webspace = $request->get('_sulu')->getAttribute('webspace');
        $localization = $request->get('_sulu')->getAttribute('localization');

        if (!$localization) {
            $localization = $portal->getDefaultLocalization();
        }

        $provider = $this->get('sulu_website.sitemap.pool')->getProvider($alias);
        $entries = $provider->generate(
            $page,
            $portal->getKey(),
            $localization->getLocale()
        );

        return $this->render(
            'SuluWebsiteBundle:Sitemap:sitemap.xml.twig',
            [
                'webspaceKey' => $webspace->getKey(),
                'locale' => $localization->getLocale(),
                'defaultLocale' => $portal->getXDefaultLocalization()->getLocale(),
                'domain' => $request->getHttpHost(),
                'scheme' => $request->getScheme(),
                'entries' => $entries,
            ]
        );
    }
}
