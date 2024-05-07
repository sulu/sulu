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

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\CategoryBundle\Admin\CategoryAdmin;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordInterface;

class CategoryKeywordAddedEvent extends DomainEvent
{
    public function __construct(
        private CategoryInterface $category,
        private KeywordInterface $keyword
    ) {
        parent::__construct();
    }

    public function getCategory(): CategoryInterface
    {
        return $this->category;
    }

    public function getKeyword(): CategoryInterface
    {
        return $this->category;
    }

    public function getEventType(): string
    {
        return 'keyword_added';
    }

    public function getEventContext(): array
    {
        return [
            'keywordId' => $this->keyword->getId(),
            'keywordTitle' => $this->keyword->getKeyword(),
        ];
    }

    public function getResourceKey(): string
    {
        return CategoryInterface::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string) $this->category->getId();
    }

    public function getResourceLocale(): string
    {
        return $this->keyword->getLocale();
    }

    public function getResourceTitle(): ?string
    {
        $translation = $this->getCategoryTranslation();

        return $translation ? $translation->getTranslation() : null;
    }

    public function getResourceTitleLocale(): ?string
    {
        $translation = $this->getCategoryTranslation();

        return $translation ? $translation->getLocale() : null;
    }

    private function getCategoryTranslation(): ?CategoryTranslationInterface
    {
        if (!$translation = $this->category->findTranslationByLocale($this->keyword->getLocale())) {
            return $this->category->findTranslationByLocale($this->category->getDefaultLocale()) ?: null;
        }

        return $translation;
    }

    public function getResourceSecurityContext(): ?string
    {
        return CategoryAdmin::SECURITY_CONTEXT;
    }
}
