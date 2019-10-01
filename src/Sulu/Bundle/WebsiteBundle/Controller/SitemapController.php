<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

use Sulu\Bundle\HttpCacheBundle\Cache\SuluHttpCache;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderPoolInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\XmlSitemapDumperInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\XmlSitemapRenderer;
use Sulu\Component\Webspace\Portal;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Renders a xml sitemap.
 */
class SitemapController
{
    /**
     * @var XmlSitemapRenderer
     */
    private $xmlSitemapRenderer;

    /**
     * @var SitemapProviderPoolInterface
     */
    private $sitemapProviderPool;

    /**
     * @var XmlSitemapDumperInterface
     */
    private $xmlSitemapDumper;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var int
     */
    private $cacheLifeTime;

    public function __construct(
        XmlSitemapRenderer $xmlSitemapRenderer,
        SitemapProviderPoolInterface $sitemapProviderPool,
        XmlSitemapDumperInterface $xmlSitemapDumper,
        Filesystem $filesystem,
        UrlGeneratorInterface $router,
        int $cacheLifeTime
    ) {
        $this->xmlSitemapRenderer = $xmlSitemapRenderer;
        $this->sitemapProviderPool = $sitemapProviderPool;
        $this->xmlSitemapDumper = $xmlSitemapDumper;
        $this->filesystem = $filesystem;
        $this->router = $router;
        $this->cacheLifeTime = $cacheLifeTime;
    }

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
        if (null !== ($response = $this->getDumpedIndexResponse($request))) {
            return $response;
        }

        $sitemap = $this->xmlSitemapRenderer->renderIndex();
        if (!$sitemap) {
            $aliases = array_keys($this->sitemapProviderPool->getProviders());

            return $this->sitemapPaginatedAction($request, reset($aliases), 1);
        }

        return $this->setCacheLifetime(new Response($sitemap));
    }

    /**
     * Returns index-response if dumped file exists.
     *
     * @param Request $request
     *
     * @return null|BinaryFileResponse
     */
    private function getDumpedIndexResponse(Request $request)
    {
        /** @var Portal $portal */
        $portal = $request->get('_sulu')->getAttribute('portal');
        $localization = $request->get('_sulu')->getAttribute('localization');
        if (!$localization) {
            $localization = $portal->getXDefaultLocalization();
        }

        $path = $this->xmlSitemapDumper->getIndexDumpPath(
            $request->getScheme(),
            $portal->getWebspace()->getKey(),
            $localization->getLocale(),
            $request->getHttpHost()
        );

        if (!$this->filesystem->exists($path)) {
            return;
        }

        return $this->createBinaryFileResponse($path);
    }

    /**
     * Redirect to the first page of a single sitemap provider.
     *
     * @param string $alias
     *
     * @return Response
     */
    public function sitemapAction($alias)
    {
        if (!$this->sitemapProviderPool->hasProvider($alias)) {
            return new Response(null, 404);
        }

        return new RedirectResponse(
            $this->router->generate(
                'sulu_website.paginated_sitemap',
                ['alias' => $alias, 'page' => 1]
            ),
            301
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
        if (null !== ($response = $this->getDumpedSitemapResponse($request, $alias, $page))) {
            return $response;
        }

        $portal = $request->get('_sulu')->getAttribute('portal');
        $localization = $request->get('_sulu')->getAttribute('localization');
        if (!$localization) {
            $localization = $portal->getXDefaultLocalization();
        }

        $sitemap = $this->xmlSitemapRenderer->renderSitemap(
            $alias,
            $page,
            $localization->getLocale(),
            $portal,
            $request->getHttpHost(),
            $request->getScheme()
        );

        if (!$sitemap) {
            return new Response(null, 404);
        }

        return $this->setCacheLifetime(new Response($sitemap));
    }

    /**
     * Returns index-response if dumped file exists.
     *
     * @param Request $request
     * @param string $alias
     * @param int $page
     *
     * @return null|BinaryFileResponse
     */
    private function getDumpedSitemapResponse(Request $request, $alias, $page)
    {
        /** @var Portal $portal */
        $portal = $request->get('_sulu')->getAttribute('portal');
        $localization = $request->get('_sulu')->getAttribute('localization');
        if (!$localization) {
            $localization = $portal->getXDefaultLocalization();
        }

        $path = $this->xmlSitemapDumper->getDumpPath(
            $request->getScheme(),
            $portal->getWebspace()->getKey(),
            $localization->getLocale(),
            $request->getHttpHost(),
            $alias,
            $page
        );

        if (!$this->filesystem->exists($path)) {
            return;
        }

        return $this->createBinaryFileResponse($path);
    }

    /**
     * Set cache headers.
     *
     * @param Response $response
     *
     * @return Response
     */
    private function setCacheLifetime(Response $response)
    {
        $response->headers->set(
            SuluHttpCache::HEADER_REVERSE_PROXY_TTL,
            $response->getAge() + $this->cacheLifeTime
        );

        return $response->setMaxAge(240)
            ->setSharedMaxAge(960);
    }

    /**
     * Create a binary file response.
     *
     * @param string $file
     *
     * @return BinaryFileResponse
     */
    private function createBinaryFileResponse($file)
    {
        $response = new BinaryFileResponse($file);
        $response->headers->addCacheControlDirective('no-store', true);

        return $response;
    }
}
