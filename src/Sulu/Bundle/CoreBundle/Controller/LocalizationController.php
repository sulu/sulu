<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;

/**
 * Controller which returns
 */
class LocalizationController extends FOSRestController implements ClassResourceInterface
{
    public function cgetAction()
    {
        /** @var LocalizationManagerInterface $localizationManager */
        $localizationManager = $this->get('sulu.core.localization_manager');

        return $this->handleView(
            $this->view(array_values($localizationManager->getLocalizations())),
            200
        );
    }
} 
