<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller\Repository;


use Doctrine\Bundle\DoctrineBundle\Registry;

class GetContactName implements GetContactInterface
{

    /**
     * @var Registry
     */
    private $doctrine;

    function __construct($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * TODO do it better
     * TODO Performance issue
     * TODO DataGrid should be able to handle Objects (perhaps template for cell)
     *
     * @param $id
     * @return string
     */
    public function getContact($id)
    {
        $user = $this->getDoctrine()->getRepository('SuluSecurityBundle:User')->find($id);

        if ($user !== null) {
            $contact = $user->getContact();

            return $contact->getFirstname() . " " . $contact->getLastname();
        } else {
            return "";
        }
    }

    protected function getDoctrine()
    {
        return $this->doctrine;
    }
}
