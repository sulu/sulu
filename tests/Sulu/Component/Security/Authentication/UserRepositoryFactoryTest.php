<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authentication;

class UserRepositoryFactoryTest extends \PHPUnit_Framework_TestCase
{
    private function getUserRepositoryFactoryMock($system)
    {
        $userRepositoryMock = $this->getMockBuilder('Sulu\Component\Security\Authentication\UserRepositoryInterface')->getMock();
        $userRepositoryMock->expects($this->once())->method('init')->with($system, null);

        $entityManagerMock = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManagerMock->expects($this->once())->method('getRepository')->will(
            $this->returnValueMap(
                array(
                    array('Sulu\Bundle\SecurityBundle\Entity\User', $userRepositoryMock),
                )
            )
        );

        return new UserRepositoryFactory($entityManagerMock, 'Sulu');
    }

    public function testGetRepository()
    {
        $userRepositoryFactory = $this->getUserRepositoryFactoryMock('Sulu');

        $userRepositoryFactory->getRepository();
    }
}
