<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Domain\Model;

use Symfony\Component\Uid\Ulid;

/**
 * @final
 */
class Route
{
    /** @internal */
    public const HISTORY_RESOURCE_KEY = 'route_history';

    /** @internal */
    private const TEMPORARY_RESOURCE_IDENTIFIER = 'temp';

    private ?int $id = null;

    private ?string $site;

    private string $locale;

    private string $slug;

    private ?Route $parentRoute;

    private string $resourceKey;

    private string $resourceId;

    /**
     * @internal
     *
     * @var (callable(): string)|null
     */
    private mixed $resourceIdCallable = null;

    public function __construct(string $resourceKey, string $resourceId, string $locale, string $slug, ?string $site = null, ?Route $parentRoute = null)
    {
        $this->resourceKey = $resourceKey;
        $this->resourceId = $resourceId;
        $this->locale = $locale;
        $this->slug = $slug;
        $this->site = $site;
        $this->parentRoute = $parentRoute;
    }

    /**
     * @experimental This method is experimental and may be removed or changed in the future.
     *
     *    MM
     *   <' \___/|          _
     *     \_  _/    or    / \
     *       ][            \_/
     *
     * There might be a chicken egg problem when try to create a route for an entity which is not flushed yet. This
     * can happen if the id of the entity is auto increment. So we need generate a temporary resourceId. Which we
     * later replace with the real id of that entity after we flushed, we store a callback method to handle this.
     *
     * @param (callable(): string) $resourceIdCallable Example of a callable: fn(): string => (string) $entity->getId()
     */
    public static function createRouteWithTempId(string $resourceKey, callable $resourceIdCallable, string $locale, string $slug, ?string $site = null, ?Route $parentRoute = null): Route
    {
        // to avoid confuses with widely used uuids in our own code we use a not so widely used ULID in base58 format
        $tempId = self::TEMPORARY_RESOURCE_IDENTIFIER . '::' . (new Ulid())->toBase58();

        $route = new self(
            $resourceKey,
            $tempId,
            $locale,
            $slug,
            $site,
            $parentRoute
        );

        $route->resourceIdCallable = $resourceIdCallable;

        return $route;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function setParentRoute(?Route $parentRoute): static
    {
        $this->parentRoute = $parentRoute;

        return $this;
    }

    public function getParentRoute(): ?Route
    {
        return $this->parentRoute;
    }

    public function getId(): int
    {
        \assert(null !== $this->id, 'Do not access before persist and flush the entity to doctrine.');

        return $this->id;
    }

    public function getSite(): ?string
    {
        return $this->site;
    }

    public function setSite(?string $site): static
    {
        $this->site = $site;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    /**
     * @internal
     */
    public function hasTemporaryId(): bool
    {
        return \str_starts_with($this->resourceId, self::TEMPORARY_RESOURCE_IDENTIFIER . '::');
    }

    /**
     * @internal
     */
    public function generateRealResourceId(): string
    {
        \assert(null !== $this->resourceIdCallable, 'This method should only be called on routes with temporary id.');

        $newResourceId = ($this->resourceIdCallable)();

        \assert(\is_string($newResourceId), 'New resourceId is expected to be always a string but got: ' . \get_debug_type($newResourceId)); // @phpstan-ignore-line function.alreadyNarrowedType

        // TODO we maybe should do $this->>resourceId = $newResourceId here but we need to check how we not trigger changes inside doctrine unit of work

        return $newResourceId;
    }
}
