<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Reference\Provider;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Reference\Provider\AbstractDocumentReferenceProvider;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Bundle\SnippetBundle\Admin\SnippetAdmin;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;

/**
 * @final
 *
 * @internal
 */
class SnippetReferenceProvider extends AbstractDocumentReferenceProvider
{
    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        StructureManagerInterface $structureManager,
        ExtensionManagerInterface $extensionManager,
        ReferenceRepositoryInterface $referenceRepository,
        DocumentInspector $documentInspector,
    ) {
        parent::__construct(
            $contentTypeManager,
            $structureManager,
            $extensionManager,
            $referenceRepository,
            $documentInspector,
            Structure::TYPE_SNIPPET,
            SnippetAdmin::SECURITY_CONTEXT
        );
    }

    public static function getResourceKey(): string
    {
        return SnippetDocument::RESOURCE_KEY;
    }
}
