<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper;

/**
 * @deprecated use events of DocumentManager instead
 */
final class ContentEvents
{
    /**
     * Thrown after a structure node is persisted in the PHPCR session.
     *
     * @var string
     */
    const NODE_POST_SAVE = 'sulu.content.node.post_save';

    /**
     * Thrown before a structure node is persisted in the PHPCR session.
     *
     * @var string
     */
    const NODE_PRE_SAVE = 'sulu.content.node.pre_save';

    /**
     * Thrown after a node has been order. Note. Thrown before session is saved.
     *
     * @var string
     */
    const NODE_ORDER = 'sulu.content.node.order';

    /**
     * Thrown before structure node is loaded.
     *
     * @var string
     */
    const NODE_LOAD = 'sulu.content.node.load';

    /**
     * Thrown before a structure node is deleted.
     *
     * @var string
     */
    const NODE_PRE_DELETE = 'sulu.content.node.pre_delete';

    /**
     * Thrown after a structure node is deleted.
     *
     * @var string
     */
    const NODE_POST_DELETE = 'sulu.content.node.post_delete';
}
