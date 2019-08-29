<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\Routing;

interface RouteBuilderInterface
{
    public function setView(string $view): self;

    public function setOption(string $key, $value): self;

    public function setAttributeDefault(string $key, string $value): self;

    public function setParent(string $parent): self;

    public function addRerenderAttribute(string $attribute): self;

    public function getRoute(): Route;
}
