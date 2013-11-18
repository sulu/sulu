<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\Rlp\Strategy;

use PHPCR\NodeInterface;

/**
 * InterfaceDefinition of Resource Locator Path Strategy
 */
interface RLPStrategyInterface {

    /**
     * returns name of RLP Strategy (e.g. whole-tree)
     * @return string
     */
    public function getName();

    /**
     * returns whole path for given ContentNode
     * @param string $title title of new node
     * @param string $parentPath parent path of new contentNode
     * @param string $portal key of portal
     * @return string whole path
     */
    public function generate($title, $parentPath, $portal);

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
     * checks if path is valid
     * @param string $path path of route
     * @param string $portal key of portal
     * @return bool
     */
    public function isValid($path, $portal);
}
