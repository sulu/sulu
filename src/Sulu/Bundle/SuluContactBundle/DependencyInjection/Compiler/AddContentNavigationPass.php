<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\DependencyInjection\Compiler;

use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\ContentNavigationPass;

/**
 * Add all services with the tag "sulu.contact.content_navigation" to the content navigation
 *
 * @package Sulu\Bundle\AdminBundle\DependencyInjection\Compiler
 */
class AddContentNavigationPass extends ContentNavigationPass
{

    public function __construct()
    {
        $this->tag = 'sulu.contact.admin.content_navigation';
        $this->serviceName = 'sulu_contact.admin.content_navigation';
    }

}
