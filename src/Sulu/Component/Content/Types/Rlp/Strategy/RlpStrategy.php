<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\Rlp\Strategy;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Exception\ResourceLocatorNotValidException;
use Sulu\Component\Content\Types\Rlp\Mapper\RlpMapperInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\PHPCR\PathCleanupInterface;
use Sulu\Component\Util\SuluNodeHelper;

/**
 * Base class for Resource Locator Path Strategy.
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
     * @var DocumentInspector
     */
    protected $documentInspector;

    public function __construct(
        $name,
        RlpMapperInterface $mapper,
        PathCleanupInterface $cleaner,
        StructureManagerInterface $structureManager,
        ContentTypeManagerInterface $contentTypeManager,
        SuluNodeHelper $nodeHelper,
        DocumentInspector $documentInspector
    ) {
        $this->name = $name;
        $this->mapper = $mapper;
        $this->cleaner = $cleaner;
        $this->structureManager = $structureManager;
        $this->contentTypeManager = $contentTypeManager;
        $this->nodeHelper = $nodeHelper;
        $this->documentInspector = $documentInspector;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function generateForUuid($title, $uuid, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $parentPath = $this->mapper->getParentPath($uuid, $webspaceKey, $languageCode, $segmentKey);

        return $this->generate($title, $parentPath, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * internal generator.
     *
     * @param $title
     * @param $parentPath
     *
     * @return string
     */
    abstract protected function generatePath($title, $parentPath = null);

    /**
     * {@inheritdoc}
     */
    public function save(ResourceSegmentBehavior $document, $userId)
    {
        $path = $document->getResourceSegment();
        $webspaceKey = $this->documentInspector->getWebspace($document);
        $languageCode = $this->documentInspector->getLocale($document);

        try {
            $treeValue = $this->loadByContent($document);
        } catch (ResourceLocatorNotFoundException $e) {
            $treeValue = null;
        }

        if ($treeValue === $path) {
            return;
        }

        if (!$this->isValid($path, $webspaceKey, $languageCode)) {
            throw new ResourceLocatorNotValidException($path);
        }

        if (!$this->mapper->unique($path, $webspaceKey, $languageCode)) {
            $treeContent = $this->loadByResourceLocator($path, $webspaceKey, $languageCode);

            // FIXME Required because jackalope-doctrine-dbal does not return references which only exist in the current
            // session. If it would loadByContent would already return some value, which would make this check obsolete.
            if ($treeContent === $this->documentInspector->getUuid($document)) {
                return;
            }

            throw new ResourceLocatorAlreadyExistsException($path, $treeContent);
        }

        $this->mapper->save($document);

        $this->adaptResourceLocators($document, $userId);
    }

    /**
     * adopts resource locator of children by iteration.
     *
     * @param ResourceSegmentBehavior $document
     * @param int $userId
     */
    private function adaptResourceLocators(ResourceSegmentBehavior $document, $userId)
    {
        if (!$document instanceof ChildrenBehavior) {
            return;
        }

        $webspaceKey = $this->documentInspector->getWebspace($document);
        $languageCode = $this->documentInspector->getLocale($document);

        foreach ($document->getChildren() as $childDocument) {
            $childNode = $this->documentInspector->getNode($childDocument);

            // determine structure
            $templatePropertyName = $this->nodeHelper->getTranslatedPropertyName('template', $languageCode);

            if (!$childNode->hasProperty($templatePropertyName)) {
                continue;
            }

            $template = $childNode->getPropertyValue($templatePropertyName);
            $structure = $this->structureManager->getStructure($template);

            if (!$structure->hasTag('sulu.rlp')) {
                continue;
            }

            // get rlp
            try {
                $rlp = $this->loadByContent($childDocument);
            } catch (ResourceLocatorNotFoundException $ex) {
                $childNode->getSession()->save();

                $rlpPart = $childNode->getPropertyValue(
                    $this->nodeHelper->getTranslatedPropertyName('title', $languageCode)
                );
                $parentRlp = $this->mapper->getParentPath(
                    $childNode->getIdentifier(),
                    $webspaceKey,
                    $languageCode
                );

                // generate new resourcelocator
                $rlp = $this->generate(
                    $rlpPart,
                    $parentRlp,
                    $webspaceKey,
                    $languageCode
                );
            }

            // build new resource segment based on parent changes
            $rlpParts = explode('/', $rlp);
            $newRlp = $document->getResourceSegment() . '/' . $rlpParts[count($rlpParts) - 1];

            // determine rlp property
            $property = $structure->getPropertyByTagName('sulu.rlp');
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());
            $property->setValue($newRlp);
            $childDocument->setResourceSegment($newRlp);

            // write value to node
            $translatedProperty = $this->nodeHelper->getTranslatedProperty($property, $languageCode);
            $contentType->write($childNode, $translatedProperty, $userId, $webspaceKey, $languageCode, null);
            $this->save($childDocument, $userId);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loadByContent(ResourceSegmentBehavior $document)
    {
        // delegate to mapper
        return $this->mapper->loadByContent(
            $this->documentInspector->getNode($document),
            $this->documentInspector->getWebspace($document),
            $this->documentInspector->getLocale($document),
            null
        );
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode, $segmentKey = null)
    {
        // delegate to mapper
        return $this->mapper->loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        return $path !== '/' && $this->cleaner->validate($path);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByPath($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $this->mapper->deleteByPath($path, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * {@inheritdoc}
     */
    public function restoreByPath($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $this->mapper->restoreByPath($path, $webspaceKey, $languageCode, $segmentKey);
    }
}
