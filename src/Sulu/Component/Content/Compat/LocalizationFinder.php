<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Tries to find localizations in the tree down then up and if nothing is found it returns any localization.
 */
class LocalizationFinder implements LocalizationFinderInterface
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    public function __construct(WebspaceManagerInterface $webspaceManager)
    {
        $this->webspaceManager = $webspaceManager;
    }

    public function findAvailableLocale($webspaceName, array $availableLocales, $locale)
    {
        if (!$webspaceName) {
            return;
        }

        // get localization object for querying parent localizations
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceName);
        $localization = $webspace->getLocalization($locale);

        if (null === $localization) {
            return;
        }

        $resultLocalization = null;

        // find first available localization in parents
        $resultLocalization = $this->findAvailableParentLocalization(
            $availableLocales,
            $localization
        );

        // find first available localization in children, if no result is found yet
        if (!$resultLocalization) {
            $resultLocalization = $this->findAvailableChildLocalization(
                $availableLocales,
                $localization
            );
        }

        // find any localization available, if no result is found yet
        if (!$resultLocalization) {
            $resultLocalization = $this->findAvailableLocalization(
                $availableLocales,
                $webspace->getLocalizations()
            );
        }

        if (!$resultLocalization) {
            return;
        }

        return $resultLocalization->getLocale();
    }

    /**
     * Finds the next available parent-localization in which the node has a translation.
     *
     * @param string[] $availableLocales
     * @param Localization $localization The localization to start the search for
     *
     * @return null|Localization
     */
    private function findAvailableParentLocalization(array $availableLocales, Localization $localization)
    {
        do {
            if (in_array($localization->getLocale(), $availableLocales)) {
                return $localization;
            }

            // try to load parent and stop if there is no parent
            $localization = $localization->getParent();
        } while ($localization != null);

        return;
    }

    /**
     * Finds the next available child-localization in which the node has a translation.
     *
     * @param string[] $availableLocales
     * @param Localization $localization The localization to start the search for
     *
     * @return null|Localization
     */
    private function findAvailableChildLocalization(array $availableLocales, Localization $localization)
    {
        $childrenLocalizations = $localization->getChildren();

        if (!empty($childrenLocalizations)) {
            foreach ($childrenLocalizations as $childrenLocalization) {
                // return the localization if a translation exists in the child localization
                if (in_array($childrenLocalization->getLocale(), $availableLocales)) {
                    return $childrenLocalization;
                }

                // recursively call this function for checking children
                return $this->findAvailableChildLocalization($availableLocales, $childrenLocalization);
            }
        }

        // return null if nothing was found
        return;
    }

    /**
     * Finds any localization, in which the node is translated.
     *
     * @param string[] $availableLocales
     * @param Localization[] $localizations The available localizations
     *
     * @return null|Localization
     */
    private function findAvailableLocalization(array $availableLocales, array $localizations)
    {
        foreach ($localizations as $localization) {
            if (in_array($localization->getLocale(), $availableLocales)) {
                return $localization;
            }

            $children = $localization->getChildren();

            if ($children) {
                $result = $this->findAvailableLocalization($availableLocales, $children);

                if (null !== $result) {
                    return $result;
                }
            }
        }

        return;
    }
}
