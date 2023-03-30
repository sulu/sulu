<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\View;

use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;

class Badge
{
    /**
     * @Expose()
     * @Groups({"fullView"})
     */
    private string $routeName;

    /**
     * @Expose()
     * @Groups({"fullView"})
     */
    private ?string $dataPath = null;

    /**
     * @Expose()
     * @Groups({"fullView"})
     */
    private ?string $visibleCondition = null;

    /**
     * @var array<string, mixed>
     *
     * @Expose()
     * @Groups({"fullView"})
     */
    private array $requestParameters = [];

    /**
     * @var array<string, mixed>
     *
     * @Expose()
     * @Groups({"fullView"})
     */
    private array $routerAttributesToRequest = [];

    public function __construct(string $routeName, ?string $dataPath = null, ?string $visibleCondition = null)
    {
        $this->routeName = $routeName;
        $this->dataPath = $dataPath;
        $this->visibleCondition = $visibleCondition;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function getDataPath(): ?string
    {
        return $this->dataPath;
    }

    public function getVisibleCondition(): ?string
    {
        return $this->visibleCondition;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRequestParameters(): array
    {
        return $this->requestParameters;
    }

    /**
     * @param array<string, mixed> $requestParameters
     */
    public function addRequestParameters(array $requestParameters): self
    {
        $this->requestParameters = \array_merge($this->requestParameters, $requestParameters);

        return $this;
    }

    /**
     * @return array<string|int, string>
     */
    public function getRouterAttributesToRequest(): array
    {
        return $this->routerAttributesToRequest;
    }

    /**
     * @param array<string|int, string> $routerAttributesToRequest
     */
    public function addRouterAttributesToRequest(array $routerAttributesToRequest): self
    {
        $this->routerAttributesToRequest = \array_merge($this->routerAttributesToRequest, $routerAttributesToRequest);

        return $this;
    }
}
