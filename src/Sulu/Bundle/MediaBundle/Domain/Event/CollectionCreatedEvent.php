<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Domain\Event;

use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\MediaBundle\Admin\MediaAdmin;
use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;

class CollectionCreatedEvent extends DomainEvent
{
    /**
     * @var CollectionInterface
     */
    private $collection;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var mixed[]
     */
    private $payload;

    /**
     * @param mixed[] $payload
     */
    public function __construct(
        CollectionInterface $collection,
        string $locale,
        array $payload
    ) {
        parent::__construct();

        $this->collection = $collection;
        $this->locale = $locale;
        $this->payload = $payload;
    }

    public function getCollection(): CollectionInterface
    {
        return $this->collection;
    }

    public function getEventType(): string
    {
        return 'created';
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return CollectionInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->collection->getId();
    }

    public function getResourceLocale(): ?string
    {
        return $this->locale;
    }

    public function getResourceTitle(): ?string
    {
        $collection = new Collection($this->collection, $this->locale);

        return $collection->getTitle();
    }

    public function getResourceSecurityContext(): ?string
    {
        return MediaAdmin::SECURITY_CONTEXT;
    }
}
