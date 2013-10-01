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

/**
 * Repository for the User, implementing some additional functions
 * for querying objects
 */
class UserRepository extends EntityRepository
{
    /**
     * Returns roles for a specific user (includes permissions and email)
     * @param $id
     * @return array
     */
    public function findRolesOfUser($id)
    {

        $dql = 'SELECT user, userRoles, role, contact
				FROM SuluSecurityBundle:User user
                    LEFT JOIN user.userRoles userRoles
                    LEFT JOIN userRoles.role role
                    LEFT JOIN user.contact contact
				WHERE user.id = :userId';

        $query = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameters(
                array(
                    'userId' => $id
                )
            );

        $result = $query->getArrayResult();

        if(sizeof($result) > 0) {
            return $result[0];
        } else {
            return null;
        }
    }

    function __toString()
    {
        return "UserRepository";
    }


}
