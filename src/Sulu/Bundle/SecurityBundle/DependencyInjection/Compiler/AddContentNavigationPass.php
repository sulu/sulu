<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\ContentNavigationPass;

/**
 * Add all services with the tag "sulu.security.admin.roles_navigation" to the content navigation
 */
class AddContentNavigationPass extends ContentNavigationPass
{
    public function __construct()
    {
        $this->tag = 'sulu.security.admin.roles_navigation';
        $this->serviceName = 'sulu_security.admin.roles_navigation';
    }
}
