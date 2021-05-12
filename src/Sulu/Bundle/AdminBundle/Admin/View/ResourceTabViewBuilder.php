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

class ResourceTabViewBuilder implements ResourceTabViewBuilderInterface
{
    use ViewBuilderTrait;
    use FormViewBuilderTrait;
    use TabViewChildBuilderTrait, TabViewBuilderTrait {
        TabViewChildBuilderTrait::setTabTitleToView insteadof TabViewBuilderTrait;
        TabViewChildBuilderTrait::setTabConditionToView insteadof TabViewBuilderTrait;
        TabViewChildBuilderTrait::setTabOrderToView insteadof TabViewBuilderTrait;
        TabViewChildBuilderTrait::setTabPriorityToView insteadof TabViewBuilderTrait;
        TabViewChildBuilderTrait::addTabBadgesToView insteadof TabViewBuilderTrait;
        TabViewChildBuilderTrait::setDisableTabGapToView insteadof TabViewBuilderTrait;
    }

    const TYPE = 'sulu_admin.resource_tabs';

    public function __construct(string $name, string $path)
    {
        $this->view = new View($name, $path, static::TYPE);
    }

    public function setResourceKey(string $resourceKey): ResourceTabViewBuilderInterface
    {
        $this->view->setOption('resourceKey', $resourceKey);

        return $this;
    }

    public function addLocales(array $locales): ResourceTabViewBuilderInterface
    {
        $oldLocales = $this->view->getOption('locales');
        $newLocales = $oldLocales ? \array_merge($oldLocales, $locales) : $locales;
        $this->view->setOption('locales', $newLocales);

        return $this;
    }

    public function setBackView(string $backView): ResourceTabViewBuilderInterface
    {
        $this->setBackViewToView($this->view, $backView);

        return $this;
    }

    public function addRouterAttributesToBackView(
        array $routerAttributesToBackView
    ): ResourceTabViewBuilderInterface {
        $this->addRouterAttributesToBackViewToView($this->view, $routerAttributesToBackView);

        return $this;
    }

    public function setTitleProperty(string $titleProperty): ResourceTabViewBuilderInterface
    {
        $this->view->setOption('titleProperty', $titleProperty);

        return $this;
    }

    public function addRouterAttributesToBlacklist(array $routerAttributesToBlacklist): ResourceTabViewBuilderInterface
    {
        $this->addRouterAttributesToBlacklistToView($routerAttributesToBlacklist);

        return $this;
    }

    public function getView(): View
    {
        if (!$this->view->getOption('resourceKey')) {
            throw new \DomainException(
                'A view for a ResourceTabs view needs a "resourceKey" option.'
                . ' You have likely forgotten to call the "setResourceKey" method.'
            );
        }

        if ($this->view->getOption('locales') && false === \strpos($this->view->getPath(), ':locale')) {
            throw new \DomainException(
                'A view for a ResourceTabs view needs a ":locale" placeholder in its URL'
                . ' if some "locales" have been set.'
            );
        }

        if (!$this->view->getOption('locales') && false !== \strpos($this->view->getPath(), ':locale')) {
            throw new \DomainException(
                'A view for a ResourceTabs view needs a ":locale" placeholder in its URL'
                . ' if some "locales" have been set.'
            );
        }

        return clone $this->view;
    }

    public function setTabTitle(string $tabTitle): ResourceTabViewBuilderInterface
    {
        $this->setTabTitleToView($this->view, $tabTitle);

        return $this;
    }

    public function setTabOrder(int $tabOrder): ResourceTabViewBuilderInterface
    {
        $this->setTabOrderToView($this->view, $tabOrder);

        return $this;
    }

    public function setTabPriority(int $tabPriority): ResourceTabViewBuilderInterface
    {
        $this->setTabPriorityToView($this->view, $tabPriority);

        return $this;
    }

    public function setTabCondition(string $tabCondition): ResourceTabViewBuilderInterface
    {
        $this->setTabConditionToView($this->view, $tabCondition);

        return $this;
    }
}
