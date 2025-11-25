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

interface CustomUrlInterface extends AuditableInterface
{
    public const RESOURCE_KEY = 'custom_urls';

    public function getId(): string;

    public function setId(string $id): static;

    public function getUuid(): string;

    public function setUuid(string $uuid): static;

    public function getTitle(): string;

    public function setTitle(string $title): static;

    public function setPublished(bool $published): static;

    public function isPublished(): bool;

    public function setWebspace(string $webspace): static;

    public function getWebspace(): string;

    public function setBaseDomain(string $baseDomain): static;

    public function getBaseDomain(): string;

    /**
     * @param array<string> $domainParts
     */
    public function setDomainParts(array $domainParts): static;

    /**
     * @return array<string>
     */
    public function getDomainParts(): array;

    public function setTargetDocument(?string $targetDocument): static;

    public function getTargetDocument(): ?string;

    public function getTargetLocale(): string;

    public function setTargetLocale(string $targetLocale): static;

    public function isCanonical(): bool;

    public function setCanonical(bool $canonical): static;

    public function isRedirect(): bool;

    public function setRedirect(bool $redirect): static;

    public function isNoFollow(): bool;

    public function setNoFollow(bool $noFollow): static;

    public function isNoIndex(): bool;

    public function setNoIndex(bool $noIndex): static;

    /**
     * @return iterable<CustomUrlRouteInterface>
     */
    public function getRoutes(): iterable;

    public function addRoute(CustomUrlRouteInterface $route): static;

    public function generateRoutes(): void;

    /**
     * @return array<mixed>
     */
    public function toArray(): array;
}
