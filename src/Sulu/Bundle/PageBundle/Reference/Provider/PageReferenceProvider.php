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
use Sulu\Bundle\DocumentManagerBundle\Reference\Provider\AbstractDocumentReferenceProvider;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;

/**
 * @final
 *
 * @internal
 */
class PageReferenceProvider extends AbstractDocumentReferenceProvider
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
            Structure::TYPE_PAGE,
            '' // TODO check what we need here
        );
    }

    public static function getResourceKey(): string
    {
        return BasePageDocument::RESOURCE_KEY;
    }

    /**
     * @return array<string, string>
     */
    protected function getReferenceViewAttributes(UuidBehavior&TitleBehavior&StructureBehavior $document, string $locale): array
    {
        $referenceViewAttributes = parent::getReferenceViewAttributes($document, $locale);

        if (!$document instanceof WebspaceBehavior) {
            return $referenceViewAttributes;
        }

        return \array_merge($referenceViewAttributes, [
            'webspace' => $document->getWebspaceName(),
        ]);
    }

    protected function getReferenceSecurityContext(WebspaceBehavior|StructureBehavior $document): string
    {
        if (!$document instanceof WebspaceBehavior) {
            throw new \RuntimeException(\sprintf(
                'Document "%s" must implement WebspaceBehavior',
                \get_class($document)
            ));
        }

        return PageAdmin::getPageSecurityContext($document->getWebspaceName());
    }
}
