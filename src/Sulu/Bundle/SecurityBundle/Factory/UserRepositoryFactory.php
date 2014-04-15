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
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    public function __construct(EntityManager $em, RequestAnalyzerInterface $requestAnalyzer = null)
    {
        $this->em = $em;
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
            $repository->setSystem('Sulu'); // FIXME Do not hardcode!
        } elseif ($webspaceSecurity = $this->requestAnalyzer->getCurrentWebspace()->getSecurity()) {
            $repository->setSystem($webspaceSecurity->getSystem());
        }

        return $repository;
    }
}
