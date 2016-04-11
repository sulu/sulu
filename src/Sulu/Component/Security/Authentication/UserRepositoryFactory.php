<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authentication;

use Doctrine\ORM\EntityManager;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

class UserRepositoryFactory implements UserRepositoryFactoryInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var string
     */
    private $suluSystem;

    /**
     * @var string
     */
    private $entityName;

    public function __construct(EntityManager $em, $suluSystem, $entityName, RequestAnalyzerInterface $requestAnalyzer = null)
    {
        $this->em = $em;
        $this->suluSystem = $suluSystem;
        $this->entityName = $entityName;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository()
    {
        /** @var UserRepositoryInterface $repository */
        $repository = $this->em->getRepository($this->entityName);
        $repository->init($this->suluSystem, $this->requestAnalyzer);

        return $repository;
    }
}
