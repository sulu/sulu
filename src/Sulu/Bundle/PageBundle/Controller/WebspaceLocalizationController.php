<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for getting localizations.
 */
class WebspaceLocalizationController extends AbstractRestController implements ClassResourceInterface
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        WebspaceManagerInterface $webspaceManager
    ) {
        parent::__construct($viewHandler);

        $this->webspaceManager = $webspaceManager;
    }

    /**
     * Returns the localizations for the given webspace.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $webspaceKey = $request->get('webspace');
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);

        if ($webspace) {
            $localizations = new CollectionRepresentation($webspace->getAllLocalizations(), 'localizations');
            $view = $this->view($localizations, 200);
        } else {
            $error = new RestException(sprintf('No webspace found for key \'%s\'', $webspaceKey));
            $view = $this->view($error->toArray(), 400);
        }

        return $this->handleView($view);
    }
}
