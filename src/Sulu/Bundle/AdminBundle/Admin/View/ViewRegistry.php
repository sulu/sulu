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

use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Exception\ParentViewNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\ViewNotFoundException;

class ViewRegistry
{
    /**
     * @var View[]
     */
    private $views;

    /**
     * @var AdminPool
     */
    private $adminPool;

    public function __construct(AdminPool $adminPool)
    {
        $this->adminPool = $adminPool;
    }

    /**
     * Returns all the views for the frontend application from all Admin objects.
     *
     * @return View[]
     */
    public function getViews(): array
    {
        if (!$this->views) {
            $this->loadViews();
        }

        return $this->views;
    }

    /**
     * @throws ViewNotFoundException
     */
    public function findViewByName(string $name): View
    {
        foreach ($this->getViews() as $view) {
            if ($view->getName() === $name) {
                return $view;
            }
        }

        throw new ViewNotFoundException($name);
    }

    private function loadViews(): void
    {
        $viewCollection = new ViewCollection();
        foreach ($this->adminPool->getAdmins() as $admin) {
            if (!$admin instanceof ViewProviderInterface) {
                continue;
            }

            $admin->configureViews($viewCollection);
        }

        $views = \array_map(function(ViewBuilderInterface $viewBuilder) {
            return $viewBuilder->getView();
        }, $viewCollection->all());

        $this->validateViews($views);

        $this->views = $this->mergeViewOptions($views);

        // prepend path when parent is set
        foreach ($this->views as $view) {
            if ($view->getParent()) {
                $parentView = $this->findViewByName($view->getParent());
                $view->prependPath($parentView->getPath());
            }
        }
    }

    /**
     * @param View[] $views
     *
     * @throws ParentViewNotFoundException
     */
    private function validateViews(array $views): void
    {
        $viewNames = \array_map(function(View $view) {
            return $view->getName();
        }, $views);

        foreach ($views as $view) {
            $viewParent = $view->getParent();

            if (!$viewParent) {
                continue;
            }

            if (!\in_array($viewParent, $viewNames)) {
                throw new ParentViewNotFoundException($viewParent, $view->getName());
            }
        }
    }

    private function mergeViewOptions(array $views, ?string $parent = null): array
    {
        /** @var View[] $childViews */
        $childViews = \array_filter($views, function(View $view) use ($parent) {
            return $view->getParent() === $parent;
        });

        if (empty($childViews)) {
            return [];
        }

        /** @var View[] $parentViews */
        $parentViews = \array_values(\array_filter($views, function(View $view) use ($parent) {
            return $view->getName() === $parent;
        }));
        $parentView = $parentViews[0] ?? null;

        $mergedViews = [];
        foreach ($childViews as $childView) {
            $mergedViews[] = $parentView ? $childView->mergeViewOptions($parentView) : $childView;
            $mergedViews = \array_merge($mergedViews, $this->mergeViewOptions($views, $childView->getName()));
        }

        return $mergedViews;
    }
}
