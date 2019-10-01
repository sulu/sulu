<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * handles languages for snippet ui.
 */
class LanguageController implements ClassResourceInterface
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    public function __construct(
        WebspaceManagerInterface $webspaceManager
    ) {
        $this->webspaceManager = $webspaceManager;
    }

    /**
     * Returns all languages in admin.
     *
     * @return JsonResponse
     */
    public function cgetAction()
    {
        $localizations = [];
        $locales = [];

        /** @var Webspace $webspace */
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $i = 0;
            foreach ($webspace->getAllLocalizations() as $localization) {
                if (!in_array($localization->getLocale(), $locales)) {
                    $locales[] = $localization->getLocale();
                    $localizations[] = [
                        'localization' => $localization->getLocale(),
                        'name' => $localization->getLocale(Localization::DASH),
                        'id' => $i++,
                    ];
                }
            }
        }

        $data = [
            '_embedded' => $localizations,
            'total' => count($localizations),
        ];

        return new JsonResponse($data);
    }
}
