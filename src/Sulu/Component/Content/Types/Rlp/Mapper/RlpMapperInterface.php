<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\Rlp\Mapper;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Types\Rlp\ResourceLocatorInformation;

/**
 * InterfaceDefinition of Resource Locator Path Mapper.
 */
interface RlpMapperInterface
{
    /**
     * returns name of mapper.
     *
     * @return string
     */
    public function getName();

    /**
     * creates a new route for given path.
     *
     * @param NodeInterface $contentNode  reference node
     * @param string        $path         path to generate
     * @param string        $webspaceKey  key of webspace
     * @param string        $languageCode
     * @param string        $segmentKey
     */
    public function save(NodeInterface $contentNode, $path, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * returns path for given contentNode.
     *
     * @param NodeInterface $contentNode  reference node
     * @param string        $webspaceKey  key of portal
     * @param string        $languageCode
     * @param string        $segmentKey
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorNotFoundException
     *
     * @return string path
     */
    public function loadByContent(NodeInterface $contentNode, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * returns path for given contentNode.
     *
     * @param string $uuid         uuid of contentNode
     * @param string $webspaceKey  key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorNotFoundException
     *
     * @return string path
     */
    public function loadByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * returns history for given contentNode.
     *
     * @param string $uuid         uuid of contentNode
     * @param string $webspaceKey  key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return ResourceLocatorInformation[]
     */
    public function loadHistoryByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * returns the uuid of referenced content node.
     *
     * @param string $resourceLocator requested RL
     * @param string $webspaceKey     key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorMovedException    resourceLocator has been moved
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorNotFoundException resourceLocator not found or has no content reference
     *
     * @return string uuid of content node
     */
    public function loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * checks if given path is unique.
     *
     * @param string $path
     * @param string $webspaceKey  key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return bool
     */
    public function unique($path, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * returns a unique path with "-1" if necessary.
     *
     * @param string $path
     * @param string $webspaceKey  key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string
     */
    public function getUniquePath($path, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * returns resource locator for parent node.
     *
     * @param string $uuid
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string
     */
    public function getParentPath($uuid, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * creates a new resourcelocator and creates the correct history.
     *
     * @param string $src          old resource locator
     * @param string $dest         new resource locator
     * @param string $webspaceKey  key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorMovedException
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorNotFoundException
     */
    public function move($src, $dest, $webspaceKey, $languageCode, $segmentKey = null);

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
}
