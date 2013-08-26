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

use DateTime;
use FOS\RestBundle\Controller\FOSRestController;
use Sulu\Bundle\ContactBundle\Entity\Contact;

class ContactsController extends FOSRestController
{
    /**
     * Shows the contact with the given Id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getContactAction($id)
    {
        $contact = $this->getDoctrine()
            ->getRepository('SuluContactBundle:Contact')
            ->find($id);

        $view = $this->view($contact, 200);

        return $this->handleView($view);
    }
}