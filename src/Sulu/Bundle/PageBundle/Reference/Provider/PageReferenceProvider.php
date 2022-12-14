<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Reference\Provider;

use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\DocumentManagerBundle\Reference\Provider\DocumentReferenceProvider;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;

class PageReferenceProvider extends DocumentReferenceProvider
{
    public function __construct(
        ContentTypeManagerInterface $contentTypeManager,
        StructureManagerInterface $structureManager,
        ReferenceRepositoryInterface $referenceRepository,
        DocumentInspector $documentInspector,
        string $structureType,
        string $referenceSecurityContext = ''
    ) {
        parent::__construct($contentTypeManager, $structureManager, $referenceRepository, $documentInspector, $structureType, $referenceSecurityContext);
    }

    protected function getReferenceSecurityContext(WebspaceBehavior|StructureBehavior $document): string
    {
        if (!$document instanceof WebspaceBehavior) {
            throw new \Exception('Document must implement WebspaceBehavior');
        }

        return PageAdmin::getPageSecurityContext($document->getWebspaceName());
    }
}
