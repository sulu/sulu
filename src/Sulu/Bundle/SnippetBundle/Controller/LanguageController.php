<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * handles languages for snippet ui
 */
class LanguageController extends Controller implements ClassResourceInterface
{
    public function cgetAction()
    {
        /** @var WebspaceManagerInterface $webspaceManager */
        $webspaceManager = $this->get('sulu_core.webspace.webspace_manager');

        $localizations = array();
        
        /** @var Webspace $webspace */
        foreach ($webspaceManager->getWebspaceCollection() as $webspace) {
            $i = 0;
            foreach ($webspace->getAllLocalizations() as $localization) {
                $localizations[] = array(
                    'localization' => $localization->getLocalization(),
                    'name' => $localization->getLocalization('-'),
                    'id' => $i++
                );
            }
        }

        $data = array(
            '_embedded' => $localizations,
            'total' => sizeof($localizations),
        );

        return new JsonResponse($data);
    }
} 
