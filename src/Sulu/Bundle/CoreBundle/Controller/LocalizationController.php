<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller which returns the localizations for the entire system.
 */
class LocalizationController extends RestController implements ClassResourceInterface
{
    /**
     * @var LocalizationManagerInterface
     */
    private $localizationManager;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        LocalizationManagerInterface $localizationManager
    ) {
        parent::__construct($viewHandler);
        $this->localizationManager = $localizationManager;
    }

    /**
     * Returns all the localizations available in this system.
     *
     * @return Response
     */
    public function cgetAction()
    {
        $representation = new CollectionRepresentation(
            array_values($this->localizationManager->getLocalizations()),
            'localizations'
        );

        return $this->handleView(
            $this->view($representation, 200)
        );
    }
}
