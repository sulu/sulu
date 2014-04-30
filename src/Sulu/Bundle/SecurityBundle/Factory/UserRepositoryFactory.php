<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Factory;

use Doctrine\ORM\EntityManager;
use Sulu\Component\Security\UserRepositoryInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

class UserRepositoryFactory implements UserRepositoryFactoryInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var string
     */
    private $suluSystem;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    public function __construct(EntityManager $em, $suluSystem, RequestAnalyzerInterface $requestAnalyzer = null)
    {
        $this->em = $em;
        $this->suluSystem = $suluSystem;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * {@inheritDoc}
     */
    public function getRepository()
    {
        /** @var UserRepositoryInterface $repository */
        $repository = $this->em->getRepository('Sulu\Bundle\SecurityBundle\Entity\User');
        if ($this->requestAnalyzer == null) {
            // if there is no request analyzer we are in the admin, and need the sulu system to login
            $repository->setSystem($this->suluSystem);
        } else {
            //if ($webspaceSecurity = $this->requestAnalyzer->getCurrentWebspace()->getSecurity()) {
            // if there is a request analyzer we are on the website and we get the security system from the webspace
            $repository->setSystem('Client'); // FIXME !!!

        }

        return $repository;
    }
}
