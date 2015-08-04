<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Symfony\Component\HttpFoundation\Request;
use Sulu\Component\Rest\RestController;

/**
 * This controller handles settings that are not limited to one user only
 */
class SettingController extends RestController implements ClassResourceInterface
{
    /**
     * @var string
     */
    protected static $entityName = 'SuluSecurityBundle:UserSetting';

    /**
     * Removes a setting with specific key and value for all users
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws MissingArgumentException
     */
    public function deleteAction(Request $request)
    {
        $key = $request->get('key');
        $value = $request->get('value');

        try {
            if (!$key) {
                throw new MissingArgumentException(static::$entityName, 'key');
            }

            if (!$value) {
                throw new MissingArgumentException(static::$entityName, '$value');
            }

            $this->getManager()->removeSettings($key, $value);
            $view = $this->view(null, 204);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Returns the setting manager
     *
     * @return object
     */
    protected function getManager()
    {
        return $this->get('sulu_security.user_setting_manager');
    }
}
