<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Permission;

use Sulu\Bundle\SecurityBundle\Security\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Implementation of Sulu specific security checks, includes a subject, the type of permission and the localization
 * @package Sulu\Bundle\SecurityBundle\Permission
 */
class SecurityChecker implements SecurityCheckerInterface
{
    /**
     * @var SecurityContextInterface
     */
    private $securityContext;

    public function __construct(SecurityContextInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * {@inheritDoc}
     */
    public function checkPermission($subject, $permission, $locale = null)
    {
        $attributes = array(
            'permission' => $permission
        );

        if ($locale !== null) {
            $attributes['locale'] = $locale;
        }

        if (is_string($subject)) {
            $subject = new SecurityContext($subject);
        }

        $granted = $this->securityContext->isGranted($attributes, $subject);

        if (!$granted) {
            throw new AccessDeniedException(
                sprintf('Permission "%s" in localization "%s" not granted', $permission, $locale)
            );
        }

        return $granted;
    }
}
