<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TrashBundle\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sulu\Bundle\TrashBundle\Domain\Exception\TrashItemTranslationNotFoundException;
use Sulu\Component\Security\Authentication\UserInterface;

class TrashItem implements TrashItemInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $resourceKey;

    /**
     * @var string
     */
    private $resourceId;

    /**
     * @var mixed[]
     */
    private $restoreData;

    /**
     * @var string|null
     */
    private $resourceSecurityContext;

    /**
     * @var string|null
     */
    private $resourceSecurityObjectType;

    /**
     * @var string|null
     */
    private $resourceSecurityObjectId;

    /**
     * @var \DateTimeImmutable
     */
    private $storeTimestamp;

    /**
     * @var UserInterface|null
     */
    private $user;

    /**
     * @var Collection<int, TrashItemTranslation>
     */
    private $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function setResourceKey(string $resourceKey): TrashItemInterface
    {
        $this->resourceKey = $resourceKey;

        return $this;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): TrashItemInterface
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    public function getRestoreData(): array
    {
        return $this->restoreData;
    }

    public function setRestoreData(array $restoreData): TrashItemInterface
    {
        $this->restoreData = $restoreData;

        return $this;
    }

    public function getResourceTitle(?string $locale = null): string
    {
        return $this->getTranslation($locale, true)->getTitle();
    }

    public function setResourceTitle(string $resourceTitle, ?string $locale = null): TrashItemInterface
    {
        if (!$this->hasTranslation($locale)) {
            $translation = new TrashItemTranslation($this, $locale, $resourceTitle);

            $this->translations->add($translation);

            return $this;
        }

        $translation = $this->getTranslation($locale, false);
        $translation->setTitle($resourceTitle);

        return $this;
    }

    public function getResourceSecurityContext(): ?string
    {
        return $this->resourceSecurityContext;
    }

    public function setResourceSecurityContext(?string $resourceSecurityContext): TrashItemInterface
    {
        $this->resourceSecurityContext = $resourceSecurityContext;

        return $this;
    }

    public function getResourceSecurityObjectType(): ?string
    {
        return $this->resourceSecurityObjectType;
    }

    public function setResourceSecurityObjectType(?string $resourceSecurityObjectType): TrashItemInterface
    {
        $this->resourceSecurityObjectType = $resourceSecurityObjectType;

        return $this;
    }

    public function getResourceSecurityObjectId(): ?string
    {
        return $this->resourceSecurityObjectId;
    }

    public function setResourceSecurityObjectId(?string $resourceSecurityObjectId): TrashItemInterface
    {
        $this->resourceSecurityObjectId = $resourceSecurityObjectId;

        return $this;
    }

    public function getStoreTimestamp(): \DateTimeImmutable
    {
        return $this->storeTimestamp;
    }

    public function setStoreTimestamp(\DateTimeImmutable $storeTimestamp): TrashItemInterface
    {
        $this->storeTimestamp = $storeTimestamp;

        return $this;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): TrashItemInterface
    {
        $this->user = $user;

        return $this;
    }

    public function getTranslation(?string $locale = null, bool $fallback = false): TrashItemTranslation
    {
        /** @var TrashItemTranslation|false $translation */
        $translation = $this->translations->filter(
            function(TrashItemTranslation $translation) use ($locale) {
                return $translation->getLocale() === $locale;
            }
        )->first();

        if (!$translation && $fallback) {
            $translation = $this->translations->first();
        }

        if (!$translation) {
            throw new TrashItemTranslationNotFoundException($locale);
        }

        return $translation;
    }

    private function hasTranslation(?string $locale): bool
    {
        return !$this->translations->filter(
            function(TrashItemTranslation $translation) use ($locale) {
                return $translation->getLocale() === $locale;
            }
        )->isEmpty();
    }
}
