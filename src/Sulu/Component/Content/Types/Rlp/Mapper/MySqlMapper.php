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


use Doctrine\Bundle\DoctrineBundle\Registry;
use PHPCR\NodeInterface;

class MySqlMapper extends RlpMapper {

    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @param Registry $doctrine
     */
    function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * creates a new route for given path
     * @param NodeInterface $contentNode reference node
     * @param string $path path to generate
     * @param string $webspaceKey key of portal
     * @return int|string id or uuid of new route
     */
    public function save(NodeInterface $contentNode, $path, $webspaceKey)
    {
        // TODO: Implement save() method.
    }

    /**
     * returns path for given contentNode
     * @param NodeInterface $contentNode reference node
     * @param string $portalKey key of portal
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorNotFoundException
     *
     * @return string path
     */
    public function loadByContent(NodeInterface $contentNode, $portalKey)
    {
        // TODO: Implement read() method.
    }

    /**
     * checks if given path is unique
     * @param string $path
     * @param string $portalKey key of portal
     * @return bool
     */
    public function unique($path, $portalKey)
    {
        // TODO: Implement unique() method.
    }

    /**
     * returns a unique path with "-1" if necessary
     * @param string $path
     * @param string $webspaceKey key of portal
     * @return string
     */
    public function getUniquePath($path, $webspaceKey)
    {
        // TODO: Implement getUniquePath() method.
    }

    /**
     * returns the uuid of referenced content node
     * @param string $resourceLocator requested RL
     * @param string $webspaceKey key of portal
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorNotFoundException
     *
     * @return string uuid of content node
     */
    public function loadByResourceLocator($resourceLocator, $webspaceKey)
    {
        // TODO: Implement load() method.
    }

    /**
     * returns path for given contentNode
     * @param string $uuid uuid of contentNode
     * @param string $webspaceKey key of portal
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorNotFoundException
     *
     * @return string path
     */
    public function loadByContentUuid($uuid, $webspaceKey)
    {
        // TODO: Implement loadByContentUuid() method.
    }

    /**
     * creates a new resourcelocator and creates the correct history
     * @param string $src old resource locator
     * @param string $dest new resource locator
     * @param string $webspaceKey key of portal
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException
     */
    public function move($src, $dest, $webspaceKey)
    {
        // TODO: Implement move() method.
    }
}
