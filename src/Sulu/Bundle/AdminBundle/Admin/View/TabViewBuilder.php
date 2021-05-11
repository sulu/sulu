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

class TabViewBuilder implements TabViewBuilderInterface
{
    use ViewBuilderTrait;
    use TabViewChildBuilderTrait, TabViewBuilderTrait {
        TabViewChildBuilderTrait::setTabTitleToView insteadof TabViewBuilderTrait;
        TabViewChildBuilderTrait::setTabConditionToView insteadof TabViewBuilderTrait;
        TabViewChildBuilderTrait::setTabOrderToView insteadof TabViewBuilderTrait;
        TabViewChildBuilderTrait::setTabPriorityToView insteadof TabViewBuilderTrait;
        TabViewChildBuilderTrait::addTabBadgesToView insteadof TabViewBuilderTrait;
        TabViewChildBuilderTrait::setDisableTabGapToView insteadof TabViewBuilderTrait;
    }

    const TYPE = 'sulu_admin.tabs';

    public function __construct(string $name, string $path)
    {
        $this->view = new View($name, $path, static::TYPE);
    }

    public function getView(): View
    {
        return clone $this->view;
    }

    public function addRouterAttributesToBlacklist(array $routerAttributesToBlacklist): TabViewBuilderInterface
    {
        $this->addRouterAttributesToBlacklistToView($routerAttributesToBlacklist);

        return $this;
    }

    public function setTabTitle(string $tabTitle): TabViewBuilderInterface
    {
        $this->setTabTitleToView($this->view, $tabTitle);

        return $this;
    }

    public function setTabOrder(int $tabOrder): TabViewBuilderInterface
    {
        $this->setTabOrderToView($this->view, $tabOrder);

        return $this;
    }

    public function setTabPriority(int $tabPriority): TabViewBuilderInterface
    {
        $this->setTabPriorityToView($this->view, $tabPriority);

        return $this;
    }

    public function setTabCondition(string $tabCondition): TabViewBuilderInterface
    {
        $this->setTabConditionToView($this->view, $tabCondition);

        return $this;
    }
}
