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

use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\CustomUrl\Manager\CustomUrlManagerInterface;
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
     * @var string
     */
    private $environment;

    public function __construct(
        CustomUrlManagerInterface $customUrlManager,
        RequestAnalyzerInterface $requestAnalyzer,
        WebspaceManagerInterface $webspaceManager,
        $environment
    ) {
        $this->customUrlManager = $customUrlManager;
        $this->requestAnalyzer = $requestAnalyzer;
        $this->webspaceManager = $webspaceManager;
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

        $customUrlDocument = $this->customUrlManager->readByUrl(
            $resourceLocator,
            $this->requestAnalyzer->getWebspace()->getKey()
        );

        if (null === $customUrlDocument) {
            return $collection;
        }

        $customUrlDocument = $this->customUrlManager->readByUrl(
            $resourceLocator,
            $this->requestAnalyzer->getWebspace()->getKey(),
            $customUrlDocument->getTargetLocale()
        );

        if (false === $customUrlDocument->isPublished()
            || $customUrlDocument->getTarget()->getWorkflowStage() !== WorkflowStage::PUBLISHED
        ) {
            return $collection;
        }

        $collection->add(
            uniqid('custom_url_route', true),
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
}
