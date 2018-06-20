<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\Routing;

trait RouteTrait
{
    abstract public function getJsBundleName();

    private function createBasicRoutes(string $resourceKey, string $path, array $locales = []): array
    {
        $addRoute = $this->createAddRoute($resourceKey, $path, $locales);
        $addDetailRoute = $this->createAddSubRoute($resourceKey, 'detail', $locales);

        $editRoute = $this->createEditRoute($resourceKey, $path, $locales);
        $editDetailRoute = $this->createEditSubRoute($resourceKey, 'detail', $locales);

        $listRoute = $this->createListRoute($resourceKey, $path, $locales)
            ->addOption('addRoute', $addDetailRoute->getName())
            ->addOption('editRoute', $editDetailRoute->getName());

        return [
            $listRoute->getName() => $listRoute,
            $addRoute->getName() => $addRoute,
            $addDetailRoute->getName() => $addDetailRoute,
            $editRoute->getName() => $editRoute,
            $editDetailRoute->getName() => $editDetailRoute,
        ];
    }

    private function createEditRoute(
        string $resourceKey,
        string $parentPath,
        array $locales = []
    ): Route {
        $bundleName = $this->getJsBundleName();

        if (!empty($locales)) {
            $parentPath .= '/:locale';
        }

        $editRoute = (new Route(sprintf('%s_%s.edit_form', $bundleName, $resourceKey), $parentPath . '/:id', 'sulu_admin.resource_tabs'))
            ->addOption('resourceKey', $resourceKey);

        if (!empty($locales)) {
            $editRoute->addOption('locales', $locales);
        }

        return $editRoute;
    }

    private function createEditSubRoute(
        string $resourceKey,
        string $name,
        array $locales = []
    ): Route {
        $bundleName = $this->getJsBundleName();

        $editSubRoute = (new Route(sprintf('%s_%s.edit_form.' . $name, $bundleName, $resourceKey), '/' . $name, 'sulu_admin.form'))
            ->addOption('tabTitle', sprintf('%s.' . $name, $bundleName))
            ->addOption('backRoute', sprintf('%s_%s.datagrid', $bundleName, $resourceKey))
            ->setParent(sprintf('%s_%s.edit_form', $bundleName, $resourceKey));

        if (!empty($locales)) {
            $editSubRoute->addOption('locales', $locales);
        }

        return $editSubRoute;
    }

    private function createAddRoute(
        string $resourceKey,
        string $parentPath,
        array $locales = []
    ): Route {
        $bundleName = $this->getJsBundleName();

        if (!empty($locales)) {
            $parentPath .= '/:locale';
        }

        $addRoute = (new Route(sprintf('%s_%s.add_form', $bundleName, $resourceKey), $parentPath . '/add', 'sulu_admin.resource_tabs'))
            ->addOption('resourceKey', $resourceKey);

        if (!empty($locales)) {
            $addRoute->addOption('locales', $locales);
        }

        return $addRoute;
    }

    private function createAddSubRoute(
        string $resourceKey,
        string $name,
        array $locales = []
    ): Route {
        $bundleName = $this->getJsBundleName();

        $addSubRoute = (new Route(sprintf('%s_%s.add_form.' . $name, $bundleName, $resourceKey), '/' . $name, 'sulu_admin.form'))
            ->addOption('tabTitle', sprintf('%s.' . $name, $bundleName))
            ->addOption('backRoute', sprintf('%s_%s.datagrid', $bundleName, $resourceKey))
            ->addOption('editRoute', sprintf('%s_%s.edit_form.' . $name, $bundleName, $resourceKey))
            ->setParent(sprintf('%s_%s.add_form', $bundleName, $resourceKey));

        if (!empty($locales)) {
            $addSubRoute->addOption('locales', $locales);
        }

        return $addSubRoute;
    }

    private function createListRoute(
        string $resourceKey,
        string $path,
        array $locales = []
    ): Route {
        $bundleName = $this->getJsBundleName();

        if (!empty($locales)) {
            $path = $path . '/:locale';
        }

        $listRoute = (new Route(sprintf('%s_%s.datagrid', $bundleName, $resourceKey), $path, 'sulu_admin.datagrid'))
            ->addOption('title', sprintf('%s.%s', $bundleName, $resourceKey))
            ->addOption('adapters', ['table'])
            ->addOption('resourceKey', $resourceKey)
            ->addOption('locales', $locales);

        if (!empty($locales)) {
            $listRoute->addOption('locales', $locales)
                ->addAttributeDefault('locale', $locales[0]);
        }

        return $listRoute;
    }
}
