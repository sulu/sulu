<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Controller;

use Sulu\Bundle\CoreBundle\Controller\RestController;

/**
 * Makes accounts available through a REST API
 * @package Sulu\Bundle\ContactBundle\Controller
 */
class AccountsController extends RestController
{
    protected $entity = 'SuluContactBundle:Account';

    public function getAccountAction($id)
    {
        return $this->processGetById(
            $id,
            function ($id) {
                return $this->getDoctrine()
                    ->getRepository($this->entity)
                    ->find($id);
            }
        );
    }
}