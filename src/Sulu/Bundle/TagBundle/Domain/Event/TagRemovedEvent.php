<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Domain\Event;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\TagBundle\Admin\TagAdmin;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

class TagRemovedEvent extends DomainEvent
{
    /**
     * @var int
     */
    private $tagId;

    /**
     * @var string
     */
    private $tagName;

    /**
     * @var mixed[]
     */
    private $context;

    /**
     * @param mixed[] $context
     */
    public function __construct(
        int $tagId,
        string $tagName,
        array $context = []
    ) {
        parent::__construct();

        $this->tagId = $tagId;
        $this->tagName = $tagName;
        $this->context = $context;
    }

    public function getEventType(): string
    {
        return 'removed';
    }

    public function getEventContext(): array
    {
        return $this->context;
    }

    public function getResourceKey(): string
    {
        return TagInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->tagId;
    }

    public function getResourceTitle(): ?string
    {
        return $this->tagName;
    }

    public function getResourceSecurityContext(): ?string
    {
        return TagAdmin::SECURITY_CONTEXT;
    }
}
