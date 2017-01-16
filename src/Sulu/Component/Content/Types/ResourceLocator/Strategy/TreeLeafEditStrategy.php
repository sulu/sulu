<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\ResourceLocator\Strategy;

use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;

/**
 * Implements RLP Strategy "tree_leaf_edit".
 *
 * The generator uses the whole tree.
 * The children will also be updated.
 * Only the last part of the resource-locator is editable.
 */
class TreeLeafEditStrategy extends ResourceLocatorStrategy implements ResourceLocatorStrategyInterface
{
    /**
     * {@inheritdoc}
     */
    public function getChildPart($resourceSegment)
    {
        $divider = strrpos($resourceSegment, '/');

        if ($divider === false) {
            return $resourceSegment;
        }

        return substr($resourceSegment, $divider + 1);
    }

    /**
     * {@inheritdoc}
     */
    public function getInputType()
    {
        return self::INPUT_TYPE_LEAF;
    }

    /**
     * {@inheritdoc}
     */
    public function save(ResourceSegmentBehavior $document, $userId)
    {
        if (false === parent::save($document, $userId)) {
            return;
        }

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
        $languageCode = $this->documentInspector->getOriginalLocale($document);

        $node = $this->documentInspector->getNode($document);
        $node->getSession()->save();

        foreach ($document->getChildren() as $childDocument) {
            // skip documents without assigned resource segment
            if (!$childDocument instanceof ResourceSegmentBehavior
                || !($currentResourceLocator = $childDocument->getResourceSegment())
            ) {
                $this->adaptResourceLocators($childDocument, $userId);
                continue;
            }

            // build new resource segment based on parent changes
            $parentUuid = $this->documentInspector->getUuid($document);
            $childPart = $this->getChildPart($currentResourceLocator);
            $newResourceLocator = $this->generate($childPart, $parentUuid, $webspaceKey, $languageCode);

            // save new resource locator
            $childNode = $this->documentInspector->getNode($childDocument);
            $templatePropertyName = $this->nodeHelper->getTranslatedPropertyName('template', $languageCode);
            $template = $childNode->getPropertyValue($templatePropertyName);
            $structure = $this->structureManager->getStructure($template);

            $property = $structure->getPropertyByTagName('sulu.rlp');
            $property->setValue($newResourceLocator);
            $contentType = $this->contentTypeManager->get($property->getContentTypeName());
            $translatedProperty = $this->nodeHelper->getTranslatedProperty($property, $languageCode);
            $contentType->write($childNode, $translatedProperty, $userId, $webspaceKey, $languageCode, null);

            $childDocument->setResourceSegment($newResourceLocator);

            // do not save routes if unpublished
            if (!$childDocument->getPublished()) {
                $this->adaptResourceLocators($childDocument, $userId);
            } else {
                $this->save($childDocument, $userId);
            }
        }
    }
}
