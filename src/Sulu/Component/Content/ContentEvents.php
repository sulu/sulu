<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

final class ContentEvents
{
    /**
     * Thrown after a structure node is persisted in the PHPCR session.
     * @var string
     */
    const NODE_SAVE = 'sulu.content.node.save';

    /**
     * Thrown before a structure node is persisted in the PHPCR session.
     * @var string
     */
    const NODE_PRE_SAVE = 'sulu.content.node.pre_save';

    /**
     * Thrown before a structure before the session save after a content move
     * operation.
     * @var string
     */
    const NODE_ORDER_BEFORE = 'sulu.content.node.order_before';

    /**
     * Thrown before Node is loaded.
     * @var string
     */
    const NODE_LOAD = 'sulu.content.node.load';
}
