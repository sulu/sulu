<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Document;

use Sulu\Component\DocumentManager\Behavior\Mapping\NodeNameBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;

/**
 * This document class is used when an unmapped node is loaded.
 */
class UnknownDocument implements NodeNameBehavior, UuidBehavior
{
    /**
     * @var string
     */
    private $nodeName;

    /**
     * @var string
     */
    private $uuid;

    /**
     * {@inheritdoc}
     */
    public function getNodeName()
    {
        return $this->nodeName;
    }

    /**
     * {@inheritdoc}
     */
    public function getUuid()
    {
        return $this->uuid;
    }
}
