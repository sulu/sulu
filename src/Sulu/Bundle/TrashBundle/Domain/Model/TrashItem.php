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
     * @var mixed[]
     */
    private $restoreData;

    /**
     * @var string
     */
    private $resourceTitle;

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
    private $timestamp;

    /**
     * @var UserInterface|null
     */
    private $user;

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function setResourceKey(string $resourceKey): TrashItemInterface
    {
        $this->resourceKey = $resourceKey;

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

    public function getResourceTitle(): string
    {
        return $this->resourceTitle;
    }

    public function setResourceTitle(string $resourceTitle): TrashItemInterface
    {
        $this->resourceTitle = $resourceTitle;

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

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeImmutable $timestamp): TrashItemInterface
    {
        $this->timestamp = $timestamp;

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
}
