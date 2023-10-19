<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationProviderInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ViewProviderInterface;

/**
 * Defines all the required information from a bundle's admin class.
 */
abstract class Admin implements ViewProviderInterface, NavigationProviderInterface, SecurityContextAwareInterface
{
    /** @var string */
    public const SULU_ADMIN_SECURITY_SYSTEM = 'Sulu';

    /** @var string */
    public const SETTINGS_NAVIGATION_ITEM = 'sulu_admin.settings';

    public static function getPriority(): int
    {
        return 0;
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
    }

    public function getSecurityContexts()
    {
        return [];
    }

    public function getSecurityContextsWithPlaceholder()
    {
        return $this->getSecurityContexts();
    }

    public function getConfig(): ?array
    {
        return null;
    }

    public function getConfigKey(): ?string
    {
        return null;
    }
}
