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
use Sulu\Component\Content\Exception\ResourceLocatorNotValidException;
use Sulu\Component\Content\Types\Rlp\Mapper\RlpMapperInterface;
use Sulu\Component\PHPCR\PathCleanupInterface;

/**
 * base class for Resource Locator Path Strategy
 */
abstract class RlpStrategy implements RlpStrategyInterface
{
    /**
     * @var string name of strategy
     */
    protected $name;

    /**
     * @var RlpMapperInterface
     */
    protected $mapper;

    /**
     * @var PathCleanupInterface
     */
    protected $cleaner;

    /**
     * @param string $name name of RLP Strategy
     * @param RlpMapperInterface $mapper
     * @param PathCleanupInterface $cleaner
     */
    public function __construct($name, RlpMapperInterface $mapper, PathCleanupInterface $cleaner)
    {
        $this->name = $name;
        $this->mapper = $mapper;
        $this->cleaner = $cleaner;
    }

    /**
     * returns name of RLP Strategy (e.g. whole tree)
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * returns whole path for given ContentNode
     * @param string $title title of new node
     * @param string $parentPath parent path of new contentNode
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     * @return string whole path
     */
    public function generate($title, $parentPath, $webspaceKey, $languageCode, $segmentKey = null)
    {
        // title should not have a slash
        $title = str_replace('/', '-', $title);

        // get generated path from childClass
        $path = $this->generatePath($title, $parentPath);

        // cleanup path
        $path = $this->cleaner->cleanup($path, $languageCode);

        // get unique path
        $path = $this->mapper->getUniquePath($path, $webspaceKey, $languageCode, $segmentKey);

        return $path;
    }

    /**
     * returns whole path for given ContentNode
     * @param string $title title of new node
     * @param string $uuid uuid for node to generate rl
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     * @return string whole path
     */
    public function generateForUuid($title, $uuid, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $parentPath = $this->mapper->getParentPath($uuid, $webspaceKey, $languageCode, $segmentKey);

        return $this->generate($title, $parentPath, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * internal generator
     * @param $title
     * @param $parentPath
     * @return string
     */
    protected abstract function generatePath($title, $parentPath = null);

    /**
     * {@inheritdoc}
     */
    public function save(NodeInterface $contentNode, $path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        if (!$this->isValid($path, $webspaceKey, $languageCode, $segmentKey)) {
            throw new ResourceLocatorNotValidException($path);
        }

        // delegate to mapper
        return $this->mapper->save($contentNode, $path, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * creates a new resourcelocator and creates the correct history
     * @param string $src old resource locator
     * @param string $dest new resource locator
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     */
    public function move($src, $dest, $webspaceKey, $languageCode, $segmentKey = null)
    {
        // delegate to mapper
        return $this->mapper->move($src, $dest, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * returns path for given contentNode
     * @param NodeInterface $contentNode reference node
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     * @return string path
     */
    public function loadByContent(NodeInterface $contentNode, $webspaceKey, $languageCode, $segmentKey = null)
    {
        // delegate to mapper
        return $this->mapper->loadByContent($contentNode, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * returns path for given contentNode
     * @param string $uuid uuid of contentNode
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     * @return string path
     */
    public function loadByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey = null)
    {
        // delegate to mapper
        return $this->mapper->loadByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * {@inheritdoc}
     */
    public function loadHistoryByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey = null)
    {
        return $this->mapper->loadHistoryByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * returns the uuid of referenced content node
     * @param string $resourceLocator requested RL
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     * @return string uuid of content node
     */
    public function loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode, $segmentKey = null)
    {
        // delegate to mapper
        return $this->mapper->loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * checks if path is valid
     * @param string $path path of route
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     * @return bool
     */
    public function isValid($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        if (!$this->mapper->unique(
            $path,
            $webspaceKey,
            $languageCode,
            $segmentKey
        )) {
            throw new \InvalidArgumentException(sprintf(
                'Path "%s" already exists',
                $path
            ));
        }

        // check for valid signs and uniqueness
        return $this->cleaner->validate($path);
        
    }

    /**
     * deletes given resource locator node
     * @param string $path of resource locator node
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     */
    public function deleteByPath($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $this->mapper->deleteByPath($path, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * restore given resource locator
     * @param string $path of resource locator
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     */
    public function restoreByPath($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $this->mapper->restoreByPath($path, $webspaceKey, $languageCode, $segmentKey);
    }
}

