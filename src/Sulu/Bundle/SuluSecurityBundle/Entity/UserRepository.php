<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\NoResultException;

/**
 * Repository for the User, implementing some additional functions
 * for querying objects
 */
class UserRepository extends EntityRepository
{

    /**
     * Searches for a user with a specific contact id
     * @param $id
     * @return array
     */
    public function findUserByContact($id)
    {
        try {
        $dql = 'SELECT user, userRoles, role
				FROM SuluSecurityBundle:User user
                    LEFT JOIN user.userRoles userRoles
                    LEFT JOIN userRoles.role role
				WHERE user.contact = :contactId';

        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameters(
                array(
                    'contactId' => $id
                )
            );

        $result = $query->getSingleResult();

        return $result;

        } catch (NoResultException $ex) {
            return null;
        }

    }

    function __toString()
    {
        return "UserRepository";
    }


}
