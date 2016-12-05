<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Automation;

use Sulu\Component\Content\Document\Behavior\WorkflowStageBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

/**
 * Provides handler for publishing documents.
 */
class DocumentPublishHandler extends BaseDocumentHandler
{
    /**
     * @param DocumentManagerInterface $documentManager
     */
    public function __construct(DocumentManagerInterface $documentManager)
    {
        parent::__construct('sulu_content.task_handler.publish', $documentManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function handleDocument(WorkflowStageBehavior $document, $locale)
    {
        $this->documentManager->publish($document, $locale);
    }
}
