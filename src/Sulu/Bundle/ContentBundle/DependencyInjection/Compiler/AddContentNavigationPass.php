<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\DependencyInjection\Compiler;

use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\ContentNavigationPass;

class AddContentNavigationPass extends ContentNavigationPass
{
    public function __construct()
    {
        $this->tag = 'sulu_content.admin.content_navigation';
        $this->serviceName = 'sulu_content.admin.content_navigation';
    }
}
