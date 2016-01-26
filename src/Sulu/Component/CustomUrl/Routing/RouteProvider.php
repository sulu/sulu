<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Routing;

use PHPCR\Util\PathHelper;
use Sulu\Bundle\ContentBundle\Document\RouteDocument;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\CustomUrl\Manager\CustomUrlManagerInterface;
use Sulu\Component\DocumentManager\PathBuilder;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides custom-url routes.
 */
class RouteProvider implements RouteProviderInterface
{
    /**
     * @var CustomUrlManagerInterface
     */
    private $customUrlManager;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var PathBuilder
     */
    private $pathBuilder;

    /**
     * @var string
     */
    private $environment;

    public function __construct(
        CustomUrlManagerInterface $customUrlManager,
        RequestAnalyzerInterface $requestAnalyzer,
        WebspaceManagerInterface $webspaceManager,
        PathBuilder $pathBuilder,
        $environment
    ) {
        $this->customUrlManager = $customUrlManager;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->webspaceManager = $webspaceManager;
        $this->pathBuilder = $pathBuilder;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollectionForRequest(Request $request)
    {
        // TODO check url for existing "custom-url" template

        $collection = new RouteCollection();

        $resourceLocator = rtrim(sprintf('%s%s', $request->getHost(), $request->getRequestUri()), '/');
        if (substr($resourceLocator, -5, 5) === '.html') {
            $resourceLocator = substr($resourceLocator, 0, -5);
        }

        $routeDocument = $this->customUrlManager->readRouteByUrl(
            $resourceLocator,
            $this->requestAnalyzer->getWebspace()->getKey()
        );

        if (null === $routeDocument) {
            return $collection;
        }

        if ($routeDocument->isHistory()) {
            return $this->addHistoryRoute(
                $request,
                $routeDocument,
                $collection,
                $this->requestAnalyzer->getWebspace()->getKey()
            );
        }

        $customUrlDocument = $this->customUrlManager->readByUrl(
            $resourceLocator,
            $this->requestAnalyzer->getWebspace()->getKey(),
            $routeDocument->getTargetDocument()->getTargetLocale()
        );

        if (false === $customUrlDocument->isPublished()
            || ($customUrlDocument->getTarget() !== null
                && $customUrlDocument->getTarget()->getWorkflowStage() !== WorkflowStage::PUBLISHED)
        ) {
            return $collection;
        }

        $collection->add(
            uniqid('custom_url_route_', true),
            new Route(
                $request->getPathInfo(),
                [
                    '_custom_url' => $customUrlDocument,
                    '_webspace' => $this->requestAnalyzer->getWebspace(),
                    '_environment' => $this->environment,
                ]
            )
        );

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteByName($name)
    {
        // TODO: Implement getRouteByName() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutesByNames($names)
    {
        return [];
    }

    /**
     * Add redirect to current custom-url.
     *
     * @param Request $request
     * @param RouteDocument $routeDocument
     * @param RouteCollection $collection
     * @param string $webspaceKey
     *
     * @return RouteCollection
     */
    private function addHistoryRoute(
        Request $request,
        RouteDocument $routeDocument,
        RouteCollection $collection,
        $webspaceKey
    ) {
        $resourceSegment = PathHelper::relativizePath(
            $routeDocument->getTargetDocument()->getPath(),
            $this->getRoutesPath($webspaceKey)
        );

        $url = sprintf('%s://%s', $request->getScheme(), $resourceSegment);

        $collection->add(
            uniqid('custom_url_route_', true),
            new Route(
                $request->getPathInfo(),
                [
                    '_controller' => 'SuluWebsiteBundle:Default:redirect',
                    '_finalized' => true,
                    'url' => $url,
                ]
            )
        );

        return $collection;
    }

    /**
     * Return routes path for custom-url in given webspace.
     *
     * @param string $webspaceKey
     *
     * @return string
     */
    private function getRoutesPath($webspaceKey)
    {
        return $this->pathBuilder->build(['%base%', $webspaceKey, '%custom-urls%', '%custom-urls-routes%']);
    }
}
