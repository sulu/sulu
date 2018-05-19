<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

class Events
{
    /**
     * Fired when a document is persisted (mapped to a PHPCR node).
     *
     * Fired both when a document is updated and when it is persisted
     * for the first time.
     */
    const PERSIST = 'sulu_document_manager.persist';

    /**
     * Fired when a node is hydrated (a PHPCR node is mapped to a document).
     */
    const HYDRATE = 'sulu_document_manager.hydrate';

    /**
     * Fired when a document is removed via. the document manager.
     */
    const REMOVE = 'sulu_document_manager.remove';

    /**
     * Fired when a document should be refreshed.
     */
    const REFRESH = 'sulu_document_manager.refresh';

    /**
     * Fired when a document is copied via. the document manager.
     */
    const COPY = 'sulu_document_manager.copy';

    /**
     * Fired when a document is moved via. the document manager.
     */
    const MOVE = 'sulu_document_manager.move';

    /**
     * Fired when a document is created via. the document manager.
     *
     * NOTE: This event is NOT fired when a node is persisted for the first time,
     *       it is fired when a NEW INSTANCE of a document is created from the document
     *       manager, look at the PERSIST event instead.
     */
    const CREATE = 'sulu_document_manager.create';

    /**
     * Fired when the document manager should be cleared (i.e. detach all documents).
     */
    const CLEAR = 'sulu_document_manager.clear';

    /**
     * Fired when the document manager find method is called.
     */
    const FIND = 'sulu_document_manager.find';

    /**
     * Fired when the document manager reorder method is called.
     */
    const REORDER = 'sulu_document_manager.reorder';

    /**
     * Fired when the document manager publish method is called.
     */
    const PUBLISH = 'sulu_document_manager.publish';

    /**
     * Fired when the document manager unpublish method is called.
     */
    const UNPUBLISH = 'sulu_document_manager.unpublish';

    /**
     * Fired when the document discardDraft method is called.
     */
    const REMOVE_DRAFT = 'sulu_document_manager.remove_draft';

    /**
     * Fired when the document manager requests that are flush to persistent storage happen.
     */
    const FLUSH = 'sulu_document_manager.flush';

    /**
     * Fired when a query should be created from a query string.
     */
    const QUERY_CREATE = 'sulu_document_manager.query.create';

    /**
     * Fired when a new query builder should be created.
     */
    const QUERY_CREATE_BUILDER = 'sulu_document_manager.query.create_builder';

    /**
     * Fired when a PHPCR query should be executed.
     */
    const QUERY_EXECUTE = 'sulu_document_manager.query.execute';

    /**
     * Enables subscribers to define options.
     */
    const CONFIGURE_OPTIONS = 'sulu_document_manager.configure_options';

    /**
     * Enables fields to be added to the mapping.
     */
    const METADATA_LOAD = 'sulu_document_manager.metadata_load';

    /**
     * Fired when an old version of the document should be restored.
     */
    const RESTORE = 'sulu_document_manager.restore';
}
