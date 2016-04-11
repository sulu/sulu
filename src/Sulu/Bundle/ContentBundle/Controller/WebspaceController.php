<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ContentBundle\Admin\ContentAdmin;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Security\SecuredControllerInterface;

/**
 * Provides webspace rest-endpoint.
 */
class WebspaceController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    /**
     * Returns webspace config by key.
     *
     * @param string $webspaceKey
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($webspaceKey)
    {
        return $this->handleView(
            $this->view($this->get('sulu_core.webspace.webspace_manager')->findWebspaceByKey($webspaceKey))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $webspaceKey = $request->get('webspaceKey');

        return ContentAdmin::SECURITY_CONTEXT_PREFIX . $webspaceKey;
    }
}
