<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper\LocalizationFinder;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Structure\Property;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class ParentChildAnyFinder implements LocalizationFinderInterface
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var string
     */
    private $localizationNamespace;

    /**
     * @var string
     */
    private $internalPrefix;

    public function __construct(WebspaceManagerInterface $webspaceManager, $localizationNamespace, $internalPrefix)
    {
        $this->webspaceManager = $webspaceManager;
        $this->localizationNamespace = $localizationNamespace;
        $this->internalPrefix = $internalPrefix;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(NodeInterface $contentNode, $localizationCode, $webspaceKey = null)
    {
        if ($webspaceKey !== null) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableLocalization(NodeInterface $contentNode, $localizationCode, $webspaceKey = null)
    {
        // use title field to check localization availability
        $propertyName = (!empty($this->internalPrefix) ? $this->internalPrefix . '-' : '') . 'created';
        $property = new TranslatedProperty(
            new Property($propertyName, '', 'none', false, true), // FIXME none as type is a dirty hack
            $localizationCode,
            $this->localizationNamespace
        );

        // get localization object for querying parent localizations
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
        $localization = $webspace->getLocalization($localizationCode);
        $resultLocalization = null;

        // check if it already is the correct localization
        if ($contentNode->hasProperty($property->getName())) {
            $resultLocalization = $localization;
        }

        // find first available localization in parents
        if (!$resultLocalization) {
            $resultLocalization = $this->findAvailableParentLocalization(
                $contentNode,
                $localization,
                $property
            );
        }

        // find first available localization in children, if no result is found yet
        if (!$resultLocalization) {
            $resultLocalization = $this->findAvailableChildLocalization(
                $contentNode,
                $localization,
                $property);
        }

        // find any localization available, if no result is found yet
        if (!$resultLocalization) {
            $resultLocalization = $this->findAvailableLocalization(
                $contentNode,
                $webspace->getLocalizations(),
                $property
            );
        }

        return $resultLocalization->getLocalization();
    }

    /**
     * Finds the next available parent-localization in which the node has a translation
     * @param NodeInterface $contentNode The node, which properties will be checked
     * @param Localization $localization The localization to start the search for
     * @param TranslatedProperty $property The property which will be checked for the translation
     * @return Localization|null
     */
    private function findAvailableParentLocalization(
        NodeInterface $contentNode,
        Localization $localization,
        TranslatedProperty $property
    )
    {
        do {
            $property->setLocalization($localization->getLocalization('_'));
            if ($contentNode->hasProperty($property->getName())) {
                return $localization;
            }

            // try to load parent and stop if there is no parent
            $localization = $localization->getParent();
        } while ($localization != null);

        return null;
    }

    /**
     * Finds the next available child-localization in which the node has a translation
     * @param NodeInterface $contentNode The node, which properties will be checked
     * @param Localization $localization The localization to start the search for
     * @param TranslatedProperty $property The property which will be checked for the translation
     * @return null|Localization
     */
    private function findAvailableChildLocalization(
        NodeInterface $contentNode,
        Localization $localization,
        TranslatedProperty $property
    )
    {
        $childrenLocalizations = $localization->getChildren();
        if (!empty($childrenLocalizations)) {
            foreach ($childrenLocalizations as $childrenLocalization) {
                $property->setLocalization($childrenLocalization->getLocalization('_'));
                // return the localization if a translation exists in the child localization
                if ($contentNode->hasProperty($property->getName())) {
                    return $childrenLocalization;
                }

                // recursively call this function for checking children
                return $this->findAvailableChildLocalization($contentNode, $childrenLocalization, $property);
            }
        }

        // return null if nothing was found
        return null;
    }

    /**
     * Finds any localization, in which the node is translated
     * @param NodeInterface $contentNode The node, which properties will be checkec
     * @param array $localizations The available localizations
     * @param TranslatedProperty $property The property to check
     * @return null|Localization
     */
    private function findAvailableLocalization(
        NodeInterface $contentNode,
        array $localizations,
        TranslatedProperty $property
    )
    {
        $availableLocalization = null;

        foreach ($localizations as $localization) {
            /** @var Localization $localization */
            $property->setLocalization($localization->getLocalization());
            if ($contentNode->hasProperty($property->getName())) {
                return $localization;
            }

            $childrenLocalizations = $localization->getChildren();
            if (!empty($childrenLocalizations)) {
                $availableLocalization = $this->findAvailableLocalization($contentNode, $childrenLocalizations, $property);
            }

            // return an available localization
            if ($availableLocalization) {
                break;
            }
        }

        return $availableLocalization;
    }
}
