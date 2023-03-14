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

use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\AdminBundle\Controller\AdminController;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Symfony\Component\HttpFoundation\Response;

@trigger_deprecation(
    'sulu/sulu',
    '2.0',
    'The "%s" class is deprecated since, use data from "%s" instead.',
    LocalizationController::class,
    AdminController::class
);

/**
 * @deprecated Deprecated since Sulu 2.0, use data from Sulu\Bundle\AdminBundle\Controller\AdminController::configAction
 * Remember deleting the resource configuration from Sulu\Bundle\AdminBundle\DependencyInjection\SuluAdminExtension.
 */
class LocalizationController extends AbstractRestController implements ClassResourceInterface
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
            \array_values($this->localizationManager->getLocalizations()),
            'localizations'
        );

        return $this->handleView(
            $this->view($representation, 200)
        );
    }
}
