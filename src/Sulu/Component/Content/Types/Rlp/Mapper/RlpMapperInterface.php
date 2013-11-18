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
interface RlpMapperInterface {

    /**
     * returns name of mapper
     * @return string
     */
    public function getName();

    /**
     * creates a new route for given path
     * @param NodeInterface $contentNode reference node
     * @param string $path path to generate
     * @param string $portal key of portal
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException
     */
    public function save(NodeInterface $contentNode, $path, $portal);

    /**
     * returns path for given contentNode
     * @param NodeInterface $contentNode reference node
     * @param string $portal key of portal
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorNotFoundException
     *
     * @return string path
     */
    public function read(NodeInterface $contentNode, $portal);

    /**
     * returns the uuid of referenced content node
     * @param string $resourceLocator requested RL
     * @param string $portal key of portal
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorNotFoundException
     *
     * @return string uuid of content node
     */
    public function load($resourceLocator, $portal);

    /**
     * checks if given path is unique
     * @param string $path
     * @param string $portal key of portal
     * @return bool
     */
    public function unique($path, $portal);

    /**
     * returns a unique path with "-1" if necessary
     * @param string $path
     * @param string $portal key of portal
     * @return string
     */
    public function getUniquePath($path, $portal);
}
