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

namespace Sulu\CustomUrl\Domain\Model;

use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Security\Authentication\UserInterface;

interface CustomUrlInterface extends AuditableInterface
{
    public const RESOURCE_KEY = 'custom_urls';

    public function getId(): string;

    public function setId(string $id): void;

    public function getTitle(): string;

    public function setTitle(string $title): void;

    public function setPublished(bool $published): void;

    public function isPublished(): bool;

    public function setWebspace(string $webspace): void;

    public function getWebspace(): string;

    public function setBaseDomain(string $baseDomain): void;

    public function getBaseDomain(): string;

    /**
     * @param array<string> $domainParts
     */
    public function setDomainParts(array $domainParts): void;

    /**
     * @return array<string>
     */
    public function getDomainParts(): array;

    public function setTargetDocument(string $targetDocument): void;

    public function getTargetDocument(): ?string;

    public function getTargetLocale(): string;

    public function setTargetLocale(string $targetLocale): void;

    public function isCanonical(): bool;

    public function setCanonical(bool $canonical): void;

    public function isRedirect(): bool;

    public function setRedirect(bool $redirect): void;

    public function isNoFollow(): bool;

    public function setNoFollow(bool $noFollow): void;

    public function isNoIndex(): bool;

    public function setNoIndex(bool $noIndex): void;

    public function setCreator(UserInterface $user): void;

    public function setCreated(\DateTime $dateTime): void;

    /**
     * @return array<mixed>
     */
    public function toArray(): array;
}
