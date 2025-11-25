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

    public function setId(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function setPublished(bool $published): static
    {
        $this->published = $published;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setWebspace(string $webspace): static
    {
        $this->webspace = $webspace;

        return $this;
    }

    public function getWebspace(): string
    {
        return $this->webspace;
    }

    public function setBaseDomain(string $baseDomain): static
    {
        $this->baseDomain = $baseDomain;

        return $this;
    }

    public function getBaseDomain(): string
    {
        return $this->baseDomain;
    }

    public function setDomainParts(array $domainParts): static
    {
        $this->domainParts = $domainParts;

        return $this;
    }

    public function generateRoutes(): void
    {
        $this->updateRoutes();
    }

    public function getDomainParts(): array
    {
        return $this->domainParts;
    }

    public function setTargetDocument(?string $targetDocument): static
    {
        $this->targetDocument = $targetDocument;

        return $this;
    }

    public function getTargetDocument(): ?string
    {
        return $this->targetDocument;
    }

    public function getTargetLocale(): string
    {
        return $this->targetLocale;
    }

    public function setTargetLocale(string $targetLocale): static
    {
        $this->targetLocale = $targetLocale;

        return $this;
    }

    public function isCanonical(): bool
    {
        return $this->canonical;
    }

    public function setCanonical(bool $canonical): static
    {
        $this->canonical = $canonical;

        return $this;
    }

    public function isRedirect(): bool
    {
        return $this->redirect;
    }

    public function setRedirect(bool $redirect): static
    {
        $this->redirect = $redirect;

        return $this;
    }

    public function isNoFollow(): bool
    {
        return $this->noFollow;
    }

    public function setNoFollow(bool $noFollow): static
    {
        $this->noFollow = $noFollow;

        return $this;
    }

    public function isNoIndex(): bool
    {
        return $this->noIndex;
    }

    public function setNoIndex(bool $noIndex): static
    {
        $this->noIndex = $noIndex;

        return $this;
    }

    public function getRoutes(): iterable
    {
        return $this->routes;
    }

    public function addRoute(CustomUrlRouteInterface $route): static
    {
        $this->routes->add($route);

        return $this;
    }

    public function getCurrentRoute(): ?CustomUrlRouteInterface
    {
        foreach ($this->routes as $route) {
            if (!$route->isHistory()) {
                return $route;
            }
        }

        return null;
    }

    public function markRouteAsHistory(CustomUrlRouteInterface $oldRoute, ?CustomUrlRouteInterface $newRoute = null): void
    {
        // If newRoute is not provided, use the current active route
        if (!$newRoute) {
            $newRoute = $this->getCurrentRoute();
        }

        // Only mark as history if routes are different
        if ($newRoute && $oldRoute->getUuid() !== $newRoute->getUuid()) {
            $oldRoute->setHistory(true);
            $oldRoute->setTargetRoute($newRoute);
        }
    }

    private function updateRoutes(): void
    {
        if (!isset($this->baseDomain) || empty($this->domainParts)) {
            return;
        }

        $path = $this->generatePath();

        foreach ($this->routes as $route) {
            if ($route->getPath() === $path) {
                return;
            }
        }

        $oldRoute = $this->getCurrentRoute();

        $newRoute = new CustomUrlRoute($this, $path);
        $this->routes->add($newRoute);

        if ($oldRoute && $oldRoute->getPath() !== $path) {
            $oldRoute->setHistory(true);
            $oldRoute->setTargetRoute($newRoute);
        }
    }

    private function generatePath(): string
    {
        $placeholderCount = \substr_count($this->baseDomain, '*');

        if ($placeholderCount !== \count($this->domainParts)) {
            throw new MismatchingDomainPartException(
                $this->baseDomain,
                $this->domainParts,
            );
        }

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
