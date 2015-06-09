<?php
/*
 * This file is part of Sulu
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Component\Content\Security\Listener;

use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Security\Event\PermissionUpdateEvent;

class PermissionUpdateListener
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    public function __construct(DocumentManagerInterface $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function onPermissionUpdate(PermissionUpdateEvent $event)
    {
        if ($event->getType() !== WebspaceBehavior::class) {
            return;
        }
    }
}
