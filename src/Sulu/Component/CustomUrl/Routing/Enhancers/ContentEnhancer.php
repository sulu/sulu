<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Routing\Enhancers;

use Sulu\Bundle\ContentBundle\Document\BasePageDocument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\CustomUrl\Document\CustomUrlBehavior;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

/**
 * Create a structure from custom-url target.
 */
class ContentEnhancer extends AbstractEnhancer
{
    /**
     * @var DocumentInspector
     */
    private $inspector;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    public function __construct(DocumentInspector $inspector, StructureManagerInterface $structureManager)
    {
        $this->inspector = $inspector;
        $this->structureManager = $structureManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function doEnhance(
        CustomUrlBehavior $customUrl,
        Webspace $webspace,
        array $defaults,
        Request $request
    ) {
        return ['_structure' => $this->documentToStructure($customUrl->getTargetDocument())];
    }

    /**
     * {@inheritdoc}
     */
    protected function supports(CustomUrlBehavior $customUrl)
    {
        return !$customUrl->isRedirect() && $customUrl->getTargetDocument() !== null;
    }

    /**
     * Return a structure bridge corresponding to the given document.
     *
     * @param BasePageDocument $document
     *
     * @return PageBridge
     */
    protected function documentToStructure(BasePageDocument $document)
    {
        $structure = $this->inspector->getStructureMetadata($document);
        $documentAlias = $this->inspector->getMetadata($document)->getAlias();

        $structureBridge = $this->structureManager->wrapStructure($documentAlias, $structure);
        $structureBridge->setDocument($document);

        return $structureBridge;
    }
}
