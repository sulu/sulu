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

/**
 * Defines the services, which should load the best available localization for the given content node.
 */
interface LocalizationFinderInterface
{
    /**
     * Returns the best suited localization for the given content node.
     *
     * @param NodeInterface $contentNode The content node to check for localizations
     * @param string $localizationCode The desired localization
     * @param string $webspaceKey The key for the webspace, in which the language hierarchy is defined
     *
     * @return string|null
     */
    public function getAvailableLocalization(NodeInterface $contentNode, $localizationCode, $webspaceKey = null);

    /**
     * Return true if the localization finder supports the given arguments.
     *
     * @param NodeInterface $contentNode The content node to check for localizations
     * @param string $localizationCode The desired localization
     * @param string $webspaceKey The key for the webspace, in which the language hierarchy is defined
     *
     * @return bool
     */
    public function supports(NodeInterface $contentNode, $localizationCode, $webspaceKey = null);
}
