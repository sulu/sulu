<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Domain\Event;

use Sulu\Bundle\CategoryBundle\Admin\CategoryAdmin;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;

class CategoryModifiedEvent extends DomainEvent
{
    /**
     * @var CategoryInterface
     */
    private $category;

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
        CategoryInterface $category,
        string $locale,
        array $payload
    ) {
        parent::__construct();

        $this->category = $category;
        $this->locale = $locale;
        $this->payload = $payload;
    }

    public function getCategory(): CategoryInterface
    {
        return $this->category;
    }

    public function getEventType(): string
    {
        return 'modified';
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getResourceKey(): string
    {
        return CategoryInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->category->getId();
    }

    public function getResourceLocale(): ?string
    {
        return $this->locale;
    }

    public function getResourceTitle(): ?string
    {
        $translation = $this->category->findTranslationByLocale($this->locale);

        return $translation ? $translation->getTranslation() : null;
    }

    public function getResourceSecurityContext(): ?string
    {
        return CategoryAdmin::SECURITY_CONTEXT;
    }
}
