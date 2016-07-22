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
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\ContentBundle\Admin\ContentAdmin;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides webspace rest-endpoint.
 */
class WebspaceController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    /**
     * Returns webspaces.
     *
     * @return Response
     */
    public function cgetAction()
    {
        $webspaces = [];
        $securityChecker = $this->get('sulu_security.security_checker');
        foreach ($this->get('sulu_core.webspace.webspace_manager')->getWebspaceCollection() as $webspace) {
            $securityContext = $this->getSecurityContextByWebspace($webspace->getKey());
            if ($securityChecker->hasPermission(new SecurityCondition($securityContext), PermissionTypes::VIEW)) {
                $webspaces[] = $webspace;
            }
        }

        return $this->handleView($this->view(new CollectionRepresentation($webspaces, 'webspaces')));
    }

    /**
     * Returns webspace config by key.
     *
     * @param string $webspaceKey
     *
     * @return Response
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

        if (null !== $webspaceKey) {
            return $this->getSecurityContextByWebspace($webspaceKey);
        }

        return;
    }

    /**
     * Returns security-context by webspace.
     *
     * @param string $webspaceKey
     *
     * @return string
     */
    private function getSecurityContextByWebspace($webspaceKey)
    {
        return ContentAdmin::SECURITY_CONTEXT_PREFIX . $webspaceKey;
    }
}
