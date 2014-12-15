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
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Exception\ResourceLocatorNotValidException;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Content\Types\Rlp\Mapper\RlpMapperInterface;
use Sulu\Component\PHPCR\PathCleanupInterface;
use Sulu\Component\Util\SuluNodeHelper;

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
     * @var StructureManagerInterface
     */
    protected $structureManager;

    /**
     * @var ContentTypeManagerInterface
     */
    protected $contentTypeManager;

    /**
     * @var SuluNodeHelper
     */
    protected $nodeHelper;

    /**
     * Constructor
     */
    public function __construct(
        $name,
        RlpMapperInterface $mapper,
        PathCleanupInterface $cleaner,
        StructureManagerInterface $structureManager,
        ContentTypeManagerInterface $contentTypeManager,
        SuluNodeHelper $nodeHelper
    ) {
        $this->name = $name;
        $this->mapper = $mapper;
        $this->cleaner = $cleaner;
        $this->structureManager = $structureManager;
        $this->contentTypeManager = $contentTypeManager;
        $this->nodeHelper = $nodeHelper;
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
    abstract protected function generatePath($title, $parentPath = null);

    /**
     * {@inheritdoc}
     */
    public function save(NodeInterface $contentNode, $path, $userId, $webspaceKey, $languageCode, $segmentKey = null)
    {
        if (!$this->isValid($path, $webspaceKey, $languageCode, $segmentKey)) {
            throw new ResourceLocatorNotValidException($path);
        }

        // delegate to mapper
        $result = $this->mapper->save($contentNode, $path, $webspaceKey, $languageCode, $segmentKey);

        // no iteration => will be done over this save method
        $this->adaptResourceLocators($contentNode, $userId, $webspaceKey, $languageCode, $segmentKey, false);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function move(
        $src,
        $dest,
        NodeInterface $contentNode,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        // delegate to mapper
        $this->mapper->move($src, $dest, $webspaceKey, $languageCode, $segmentKey);

        $this->adaptResourceLocators($contentNode, $userId, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * adopts resource locator of children by iteration
     * @param NodeInterface $contentNode
     * @param integer $userId
     * @param string $webspaceKey
     * @param string $languageCode
     * @param bool $iterate
     * @param string $segmentKey
     */
    private function adaptResourceLocators(
        NodeInterface $contentNode,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey = null,
        $iterate = true
    ) {
        foreach ($contentNode->getNodes() as $node) {
            // determine structure
            $templatePropertyName = $this->nodeHelper->getTranslatedPropertyName('template', $languageCode);
            $template = $node->getPropertyValue($templatePropertyName);
            $structure = $this->structureManager->getStructure($template);

            // only if rlp exists
            if ($structure->hasTag('sulu.rlp')) {
                // get rlp
                try {
                    $rlp = $this->loadByContent($node, $webspaceKey, $languageCode);
                } catch (ResourceLocatorNotFoundException $ex) {
                    $contentNode->getSession()->save();

                    $rlpPart = $node->getPropertyValue(
                        $this->nodeHelper->getTranslatedPropertyName('title', $languageCode)
                    );
                    $prentRlp = $this->mapper->getParentPath(
                        $node->getIdentifier(),
                        $webspaceKey,
                        $languageCode,
                        $segmentKey
                    );

                    // generate new resourcelocator
                    $rlp = $this->generate(
                        $rlpPart,
                        $prentRlp,
                        $webspaceKey,
                        $languageCode
                    );
                }

                // determine rlp property
                $property = $structure->getPropertyByTagName('sulu.rlp');
                $contentType = $this->contentTypeManager->get($property->getContentTypeName());
                $property->setValue($rlp);

                // write value to node
                $translatedProperty = $this->nodeHelper->getTranslatedProperty($property, $languageCode);
                $contentType->write($node, $translatedProperty, $userId, $webspaceKey, $languageCode, $segmentKey);
            }

            // for node move the tree will be copied to then there is the iteration over this function
            // for node copy the iteration is done by the content-type which calls over the move function
            //     recursively this function
            if ($iterate) {
                $this->adaptResourceLocators($node, $userId, $webspaceKey, $languageCode, $segmentKey);
            }
        }
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
        return $this->cleaner->validate($path) && $this->mapper->unique(
            $path,
            $webspaceKey,
            $languageCode,
            $segmentKey
        );
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
