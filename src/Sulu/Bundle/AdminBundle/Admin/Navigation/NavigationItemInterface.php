<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\Navigation;

interface NavigationItemInterface
{
    public function setId($id);
    public function getId();
    public function setName($name);
    public function getName();
    public function setLabel(string $label = null): void;
    public function getLabel(): ?string;
    public function setIcon($icon);
    public function getIcon();
    public function setPosition($position);
    public function getPosition();
    public function hasChildren();
    public function setDisabled($disabled);
    public function getDisabled();
    public function setVisible($visible);
    public function getVisible();
    public function toArray();
}
