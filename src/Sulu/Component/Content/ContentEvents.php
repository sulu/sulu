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
     * The sulu.content.node.save event is thrown when a Node is saved.
     * The event listener receives a ContentNodeEvent instance.
     * @var string
     */
    const NODE_SAVE = 'sulu.content.node.save';

    /**
     * The sulu.content.node.load event is thrown when a Node is loaded.
     * The event listener receives a ContentNodeEvent instance.
     * @var string
     */
    const NODE_LOAD = 'sulu.content.node.load';
}
