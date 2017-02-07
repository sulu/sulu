<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for getting localizations.
 */
class WebspaceLocalizationController extends RestController implements ClassResourceInterface
{
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

        /** @var WebspaceManagerInterface $webspaceManager */
        $webspaceManager = $this->get('sulu_core.webspace.webspace_manager');

        $webspace = $webspaceManager->findWebspaceByKey($webspaceKey);

        if ($webspace) {
            $localizations = new CollectionRepresentation($webspace->getAllLocalizations(), 'localizations');
            $view = $this->view($localizations, 200);
        } else {
            $error = new RestException(sprintf('No webspace found for key \'%s\'', $webspaceKey));
            $view = $this->view($error->toArray(), 400);
        }

        return $this->handleView($view, 200);
    }
}
