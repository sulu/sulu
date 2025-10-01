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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;
use Sulu\CustomUrl\Domain\Exception\MismatchingDomainPartException;
use Symfony\Component\Uid\Uuid;

class CustomUrl implements AuditableInterface, CustomUrlInterface
{
    use AuditableTrait;

    private string $uuid;

    private string $title;

    private bool $published = false;

    private string $webspace;

    private string $baseDomain;

    /**
     * @var array<string>
     */
    private array $domainParts = [];

    private ?string $targetDocument = null;

    private string $targetLocale;

    private bool $canonical = false;

    private bool $redirect = false;

    private bool $noFollow = false;

    private bool $noIndex = false;

    /**
     * @var Collection<array-key, CustomUrlRouteInterface>
     */
    private Collection $routes; // @phpstan-ignore-line doctrine.associationType

    public function __construct(
        ?string $uuid = null,
    ) {
        $this->uuid = $uuid ?: Uuid::v7()->__toString();
        $this->routes = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->uuid;
    }

    public function setId(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setPublished(bool $published): void
    {
        $this->published = $published;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setWebspace(string $webspace): void
    {
        $this->webspace = $webspace;
    }

    public function getWebspace(): string
    {
        return $this->webspace;
    }

    public function setBaseDomain(string $baseDomain): void
    {
        $this->baseDomain = $baseDomain;
    }

    public function getBaseDomain(): string
    {
        return $this->baseDomain;
    }

    public function setDomainParts(array $domainParts): void
    {
        $this->domainParts = $domainParts;
    }

    public function generateRoutes(): void
    {
        $this->updateRoutes();
    }

    public function getDomainParts(): array
    {
        return $this->domainParts;
    }

    public function setTargetDocument(?string $targetDocument): void
    {
        $this->targetDocument = $targetDocument;
    }

    public function getTargetDocument(): ?string
    {
        return $this->targetDocument;
    }

    public function getTargetLocale(): string
    {
        return $this->targetLocale;
    }

    public function setTargetLocale(string $targetLocale): void
    {
        $this->targetLocale = $targetLocale;
    }

    public function isCanonical(): bool
    {
        return $this->canonical;
    }

    public function setCanonical(bool $canonical): void
    {
        $this->canonical = $canonical;
    }

    public function isRedirect(): bool
    {
        return $this->redirect;
    }

    public function setRedirect(bool $redirect): void
    {
        $this->redirect = $redirect;
    }

    public function isNoFollow(): bool
    {
        return $this->noFollow;
    }

    public function setNoFollow(bool $noFollow): void
    {
        $this->noFollow = $noFollow;
    }

    public function isNoIndex(): bool
    {
        return $this->noIndex;
    }

    public function setNoIndex(bool $noIndex): void
    {
        $this->noIndex = $noIndex;
    }

    public function getRoutes(): iterable
    {
        return $this->routes;
    }

    public function addRoute(CustomUrlRouteInterface $route): void
    {
        $this->routes->add($route);
    }

    private function updateRoutes(): void
    {
        // Only update routes if both baseDomain and domainParts are set
        if (!isset($this->baseDomain) || empty($this->domainParts)) {
            return;
        }

        $path = $this->generatePath();

        // Only add a new route if the path doesn't already exist
        foreach ($this->routes as $route) {
            if ($route->getPath() === $path) {
                return;
            }
        }

        $this->routes->add(new CustomUrlRoute($this, $path));
    }

    private function generatePath(): string
    {
        // Count all wildcards (*) in the baseDomain
        $placeholderCount = \substr_count($this->baseDomain, '*');

        if ($placeholderCount !== \count($this->domainParts)) {
            throw new MismatchingDomainPartException(
                $this->baseDomain,
                $this->domainParts,
            );
        }

        // Replace placeholders with actual domain parts
        $path = $this->baseDomain;
        foreach ($this->domainParts as $domainPart) {
            $result = \preg_replace('/\*/', $domainPart, $path, 1);
            if (null === $result) {
                throw new \RuntimeException('Failed to generate path from domain parts');
            }
            $path = $result;
        }

        return $path;
    }

    public function toArray(): array
    {
        $vars = \get_object_vars($this);
        unset($vars['routes']);

        return $vars;
    }
}
