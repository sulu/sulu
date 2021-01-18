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

class Badge
{
    /**
     * @var string
     */
    private $routeName;

    /**
     * @var string|null
     */
    private $dataPath;

    /**
     * @var string|null
     */
    private $visibleCondition;

    /**
     * @var array<string, mixed>
     */
    private $attributesToRequest = [];

    /**
     * @var array<string, mixed>
     */
    private $routerAttributesToRequest = [];

    public function __construct(string $routeName, ?string $dataPath = null)
    {
        $this->routeName = $routeName;
        $this->dataPath = $dataPath;
    }

    public function setRouteName(string $routeName): self
    {
        $this->routeName = $routeName;

        return $this;
    }

    public function setDataPath(?string $dataPath): self
    {
        $this->dataPath = $dataPath;

        return $this;
    }

    public function setVisibleCondition(?string $visibleCondition): self
    {
        $this->visibleCondition = $visibleCondition;

        return $this;
    }

    /**
     * @param array<string, mixed> $attributesToRequest
     */
    public function addAttributesToRequest(array $attributesToRequest): self
    {
        $this->attributesToRequest = \array_merge($this->attributesToRequest, $attributesToRequest);

        return $this;
    }

    /**
     * @param array<string|int, string> $routerAttributesToRequest
     */
    public function addRouterAttributesToRequest(array $routerAttributesToRequest): self
    {
        $this->routerAttributesToRequest = \array_merge($this->routerAttributesToRequest, $routerAttributesToRequest);

        return $this;
    }

    /**
     * @internal
     *
     * @return array<string, mixed>
     */
    public function getConfiguration(): array
    {
        return [
            'routeName' => $this->routeName,
            'dataPath' => $this->dataPath,
            'visibleCondition' => $this->visibleCondition,
            'attributesToRequest' => $this->attributesToRequest,
            'routerAttributesToRequest' => $this->routerAttributesToRequest,
        ];
    }
}
