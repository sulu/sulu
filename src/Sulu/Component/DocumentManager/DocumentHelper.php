<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Collection;

use PHPCR\NodeInterface;

/**
 * This class executes management operations on the passed node
 * It is created via. the document manager
 */
class DocumentHelper
{
    public function __construct(
        NamespaceRegistry $namespaceRegistry
    )
    {
        $this->document = $document;
    }

    public function copyLanguage($srcLocale, $destLocale)
    {
    }

    public function orderBefore($srcChildRelPath, $destChildRelPath)
    {
    }

    public function orderAt($index)
    {
    }

    public function localize($locale)
    {
        $event = new NodeToDocumentEvent($node, $document, $locale);
        $this->eventDispatcher->dispatch(Events::NODE_TO_DOCUMENT, $locale);
    }
}
