<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Component\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;

class ContextsController extends RestController implements ClassResourceInterface
{
    public function cgetAction(Request $request)
    {
        $securityContexts = $this->getAdminPool()->getSecurityContextsWithPlaceholder();
        $view = $this->view($securityContexts);

        return $this->handleView($view);
    }

    private function getAdminPool(): AdminPool
    {
        return $this->get('sulu_admin.admin_pool');
    }
}
