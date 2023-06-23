<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Reference\Refresh;

use Jackalope\Query\Row;
use Jackalope\Query\RowIterator;
use PHPCR\SessionInterface;
use Sulu\Bundle\DocumentManagerBundle\Reference\Provider\DocumentReferenceProviderInterface;
use Sulu\Bundle\ReferenceBundle\Application\Refresh\ReferenceRefresherInterface;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * @internal your code should not depend on this class
 *
 * @final
 */
class SnippetReferenceRefresher implements ReferenceRefresherInterface
{
    public function __construct(
        private SessionInterface $session,
        private WebspaceManagerInterface $webspaceManager,
        private DocumentManagerInterface $documentManager,
        private DocumentReferenceProviderInterface $documentReferenceProvider
    ) {
    }

    public static function getResourceKey(): string
    {
        return SnippetDocument::RESOURCE_KEY;
    }

    public function refresh(): \Generator
    {
        $sql2 = \sprintf("SELECT jcr:uuid FROM [nt:unstructured] as document WHERE document.[jcr:mixinTypes] = 'sulu:%s' AND isdescendantnode(document, '/cmf/snippets')", 'snippet');

        $queryManager = $this->session->getWorkspace()->getQueryManager();
        $query = $queryManager->createQuery($sql2, 'JCR-SQL2');
        $queryResult = $query->execute();

        /** @var RowIterator $rows */
        $rows = $queryResult->getRows();

        foreach ($this->webspaceManager->getAllLocalizations() as $localization) {
            $locale = $localization->getLocale();
            /** @var Row $row */
            foreach ($rows as $row) {
                /** @var string $uuid */
                $uuid = $row->getValue('jcr:uuid');
                /** @var (UuidBehavior&TitleBehavior&StructureBehavior)|null $document */
                $document = $this->documentManager->find($uuid, $locale);

                if (!$document) {
                    continue;
                }

                $this->documentReferenceProvider->updateReferences($document, $locale);

                yield $document;
            }

            $this->documentManager->clear(); // the cache is locale independent and so we need clear between locale changs
        }
    }
}