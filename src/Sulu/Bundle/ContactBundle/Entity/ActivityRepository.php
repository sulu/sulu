<?php
/*
* This file is part of the Sulu CMS.
*
* (c) MASSIVE ART WebServices GmbH
*
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

namespace Sulu\Bundle\ContactBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;

/**
 * Repository for the Codes, implementing some additional functions
 * for querying objects
 */
class ActivityRepository extends EntityRepository
{

    /**
     * Searches for an activity by id
     * @param $id
     * @return array|null
     */
    public function findActivitiesById($id){
        try {
            $qb = $this->createQueryBuilder('activity')
                ->leftJoin('activity.activityStatus', 'status')
                ->leftJoin('activity.activityType', 'type')
                ->leftJoin('activity.activityPriority', 'priority')
                ->leftJoin('activity.contact', 'contact')
                ->leftJoin('activity.account', 'account')
                ->leftJoin('activity.assignedContact', 'assignedContact')
                ->addSelect('activity')
                ->addSelect('contact')
                ->addSelect('account')
                ->addSelect('assignedContact')
                ->addSelect('status')
                ->addSelect('type')
                ->addSelect('priority')
                ->where('activity.id = :id');

            $query = $qb->getQuery();
            $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
            $query->setParameter('id', $id);

            return $query->getSingleResult();
        } catch (NoResultException $ex) {
            return null;
        }
    }

    /**
     * Returns all activities including their contact, account and assigned contact
     * @return array|null
     */
    public function findAllActivities(){
        try {
            $qb = $this->createQueryBuilder('activity')
                ->leftJoin('activity.activityStatus', 'status')
                ->leftJoin('activity.activityType', 'type')
                ->leftJoin('activity.activityPriority', 'priority')
                ->leftJoin('activity.contact', 'contact')
                ->leftJoin('activity.account', 'account')
                ->leftJoin('activity.assignedContact', 'assignedContact')
                ->addSelect('activity')
                ->addSelect('contact')
                ->addSelect('account')
                ->addSelect('assignedContact')
                ->addSelect('status')
                ->addSelect('type')
                ->addSelect('priority');

            $query = $qb->getQuery();
            $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);

            return $query->getArrayResult();
        } catch (NoResultException $ex) {
            return null;
        }
    }

}
