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

    public function __construct(EntityManager $em, $suluSystem)
    {
        $this->em = $em;
        $this->suluSystem = $suluSystem;
    }

    /**
     * {@inheritDoc}
     */
    public function getRepository()
    {
        /** @var UserRepositoryInterface $repository */
        $repository = $this->em->getRepository('Sulu\Bundle\SecurityBundle\Entity\User');

        // Set initial security system sulu.
        // If the `RequestAnalyzer` detects a security system it will get overwritten.
        $repository->setSystem($this->suluSystem);

        return $repository;
    }
}
