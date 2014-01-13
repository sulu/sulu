<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\Rlp\Mapper;

use PHPCR\NodeInterface;

/**
 * InterfaceDefinition of Resource Locator Path Mapper
 */
interface RlpMapperInterface
{

    /**
     * returns name of mapper
     * @return string
     */
    public function getName();

    /**
     * creates a new route for given path
     * @param NodeInterface $contentNode reference node
     * @param string $path path to generate
     * @param string $portalKey key of portal
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException
     */
    public function save(NodeInterface $contentNode, $path, $portalKey);

    /**
     * returns path for given contentNode
     * @param NodeInterface $contentNode reference node
     * @param string $portalKey key of portal
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorNotFoundException
     *
     * @return string path
     */
    public function loadByContent(NodeInterface $contentNode, $portalKey);

    /**
     * returns path for given contentNode
     * @param string $uuid uuid of contentNode
     * @param string $portalKey key of portal
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorNotFoundException
     *
     * @return string path
     */
    public function loadByContentUuid($uuid, $portalKey);

    /**
     * returns the uuid of referenced content node
     * @param string $resourceLocator requested RL
     * @param string $portalKey key of portal
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorNotFoundException resourceLocator not found or has no content reference
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorMovedException resourceLocator has been moved
     *
     * @return string uuid of content node
     */
    public function loadByResourceLocator($resourceLocator, $portalKey);

    /**
     * checks if given path is unique
     * @param string $path
     * @param string $portalKey key of portal
     * @return bool
     */
    public function unique($path, $portalKey);

    /**
     * returns a unique path with "-1" if necessary
     * @param string $path
     * @param string $portalKey key of portal
     * @return string
     */
    public function getUniquePath($path, $portalKey);

    /**
     * creates a new resourcelocator and creates the correct history
     * @param string $src old resource locator
     * @param string $dest new resource locator
     * @param string $portalKey key of portal
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException
     */
    public function move($src, $dest, $portalKey);
}
