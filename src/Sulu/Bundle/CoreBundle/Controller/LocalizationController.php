<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;

/**
 * Controller which returns the localizations for the entire system.
 */
class LocalizationController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Returns all the localizations available in this system.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction()
    {
        /** @var LocalizationManagerInterface $localizationManager */
        $localizationManager = $this->get('sulu.core.localization_manager');

        $representation = new CollectionRepresentation(
            array_values($localizationManager->getLocalizations()),
            'localizations'
        );

        return $this->handleView(
            $this->view($representation),
            200
        );
    }
}
