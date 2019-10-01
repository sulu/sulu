<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Controller;

use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\CustomUrlBundle\Admin\CustomUrlAdmin;
use Sulu\Component\CustomUrl\Manager\CustomUrlManagerInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @RouteResource("route")
 */
class CustomUrlRouteController extends AbstractRestController implements SecuredControllerInterface
{
    private static $relationName = 'custom_url_routes';

    /**
     * @var CustomUrlManagerInterface
     */
    private $customUrlManager;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        CustomUrlManagerInterface $customUrlManager,
        DocumentManagerInterface $documentManager,
        RequestStack $requestStack
    ) {
        parent::__construct($viewHandler);
        $this->customUrlManager = $customUrlManager;
        $this->documentManager = $documentManager;
        $this->requestStack = $requestStack;
    }

    public function cgetAction(string $webspace, string $id, Request $request)
    {
        // TODO pagination

        $historyRoutes = $this->customUrlManager->findHistoryRoutesById($id, $webspace);

        $result = [];
        foreach ($historyRoutes as $resourcelocator => $historyRoute) {
            $result[] = [
                'id' => $historyRoute->getUuid(),
                'resourcelocator' => $resourcelocator,
                'created' => $historyRoute->getCreated(),
            ];
        }

        $list = new CollectionRepresentation($result, self::$relationName);

        return $this->handleView($this->view($list));
    }

    public function cdeleteAction(string $webspace, string $id, Request $request)
    {
        $ids = array_filter(explode(',', $request->get('ids', '')));

        $manager = $this->customUrlManager;
        foreach ($ids as $id) {
            $manager->deleteRoute($webspace, $id);
        }
        $this->documentManager->flush();

        return $this->handleView($this->view());
    }

    public function getSecurityContext()
    {
        $request = $this->requestStack->getCurrentRequest();

        return CustomUrlAdmin::getCustomUrlSecurityContext($request->get('webspace'));
    }

    public function getLocale(Request $request)
    {
        return;
    }
}
