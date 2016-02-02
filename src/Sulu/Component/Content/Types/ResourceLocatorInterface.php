<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types;

use PHPCR\NodeInterface;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\Types\Rlp\Strategy\RlpStrategyInterface;

/**
 * interface for resource locator information.
 */
interface ResourceLocatorInterface extends ContentTypeInterface
{
    /**
     * returns the node uuid of referenced content node.
     *
     * @param string $resourceLocator
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string
     */
    public function loadContentNodeUuid($resourceLocator, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * reads the value for given property out of the database + sets the value of the property.
     *
     * @param string $uuid
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string
     */
    public function getResourceLocatorByUuid($uuid, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * reads the value for given property out of the database + sets the value of the property.
     *
     * @param NodeInterface $node
     * @param string        $webspaceKey
     * @param string        $languageCode
     * @param string        $segmentKey
     *
     * @return string
     */
    public function getResourceLocator(NodeInterface $node, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * returns a list of history resource locators.
     *
     * @param string $uuid
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return ResourceLocatorInformation[]
     */
    public function loadHistoryByUuid($uuid, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * deletes given resource locator node.
     *
     * @param string $path         of resource locator node
     * @param string $webspaceKey  key of portal
     * @param string $languageCode
     * @param string $segmentKey
     */
    public function deleteByPath($path, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * restore given resource locator.
     *
     * @param string $path         of resource locator
     * @param string $webspaceKey  key of portal
     * @param string $languageCode
     * @param string $segmentKey
     */
    public function restoreByPath($path, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * @return RlpStrategyInterface
     */
    public function getStrategy();
}
