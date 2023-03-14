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

trait TabViewBuilderTrait
{
    use TabViewChildBuilderTrait {
        setTabTitleToView as parentSetTabTitleToView;
        setTabConditionToView as parentSetTabConditionToView;
        setTabOrderToView as parentSetTabOrderToView;
        setTabPriorityToView as parentSetTabPriorityToView;
    }

    /**
     * @deprecated since Sulu 2.1 and will be removed in Sulu 3.0
     *
     * @see TabViewChildBuilderTrait::setTabTitleToView()
     */
    private function setTabTitleToView(View $view, string $tabTitle): void
    {
        @trigger_deprecation('sulu/sulu', '2.1', 'The method TabViewBuilderTrait::setTabTitleToView() is deprecated. Use TabViewChildBuilderTrait::setTabTitleToView() instead.');

        $this->parentSetTabTitleToView($view, $tabTitle);
    }

    /**
     * @deprecated since Sulu 2.1 and will be removed in Sulu 3.0
     *
     * @see TabViewChildBuilderTrait::setTabConditionToView()
     */
    private function setTabConditionToView(View $view, string $tabCondition): void
    {
        @trigger_deprecation('sulu/sulu', '2.1', 'The method TabViewBuilderTrait::setTabConditionToView() is deprecated. Use TabViewChildBuilderTrait::setTabConditionToView() instead.');

        $this->parentSetTabConditionToView($view, $tabCondition);
    }

    /**
     * @deprecated since Sulu 2.1 and will be removed in Sulu 3.0
     *
     * @see TabViewChildBuilderTrait::setTabOrderToView()
     */
    private function setTabOrderToView(View $view, int $tabOrder): void
    {
        @trigger_deprecation('sulu/sulu', '2.1', 'The method TabViewBuilderTrait::setTabOrderToView() is deprecated. Use TabViewChildBuilderTrait::setTabOrderToView() instead.');

        $this->parentSetTabOrderToView($view, $tabOrder);
    }

    /**
     * @deprecated since Sulu 2.1 and will be removed in Sulu 3.0
     *
     * @see TabViewChildBuilderTrait::setTabPriorityToView()
     */
    private function setTabPriorityToView(View $view, int $tabPriority): void
    {
        @trigger_deprecation('sulu/sulu', '2.1', 'The method TabViewBuilderTrait::setTabPriorityToView() is deprecated. Use TabViewChildBuilderTrait::setTabPriorityToView() instead.');

        $this->parentSetTabPriorityToView($view, $tabPriority);
    }

    public function addRouterAttributesToBlacklistToView(array $routerAttributesToBlacklist): void
    {
        $oldRouterAttributesToBlacklist = $this->view->getOption('routerAttributesToBlacklist');
        $newRouterAttributesToBlacklist = $oldRouterAttributesToBlacklist
            ? \array_merge($oldRouterAttributesToBlacklist, $routerAttributesToBlacklist)
            : $routerAttributesToBlacklist;

        $this->view->setOption('routerAttributesToBlacklist', $newRouterAttributesToBlacklist);
    }
}
