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
     * @var string
     *
     * @Expose()
     * @Groups({"fullView"})
     */
    private $routeName;

    /**
     * @var string|null
     *
     * @Expose()
     * @Groups({"fullView"})
     */
    private $dataPath;

    /**
     * @var string|null
     *
     * @Expose()
     * @Groups({"fullView"})
     */
    private $visibleCondition;

    /**
     * @var array<string, mixed>
     *
     * @Expose()
     * @Groups({"fullView"})
     */
    private $requestParameters = [];

    /**
     * @var array<string, mixed>
     *
     * @Expose()
     * @Groups({"fullView"})
     */
    private $routerAttributesToRequest = [];

    public function __construct(string $routeName, ?string $dataPath = null, ?string $visibleCondition = null)
    {
        $this->routeName = $routeName;
        $this->dataPath = $dataPath;
        $this->visibleCondition = $visibleCondition;
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
     * @param array<string|int, string> $routerAttributesToRequest
     */
    public function addRouterAttributesToRequest(array $routerAttributesToRequest): self
    {
        $this->routerAttributesToRequest = \array_merge($this->routerAttributesToRequest, $routerAttributesToRequest);

        return $this;
    }
}
