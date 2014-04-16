<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\Factory;

use Sulu\Bundle\SecurityBundle\Factory\UserRepositoryFactory;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Security;
use Sulu\Component\Webspace\Webspace;

class UserRepositoryFactoryTest extends \PHPUnit_Framework_TestCase
{
    private function getUserRepositoryFactoryMock($system, $withRequestAnalyzer)
    {
        $userRepositoryMock = $this->getMockBuilder('Sulu\Component\Security\UserRepositoryInterface')->getMock();
        $userRepositoryMock->expects($this->once())->method('setSystem')->with($system);

        $entityManagerMock = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManagerMock->expects($this->once())->method('getRepository')->will(
            $this->returnValueMap(
                array(
                    array('Sulu\Bundle\SecurityBundle\Entity\User', $userRepositoryMock)
                )
            )
        );

        $requestAnalyzerMock = null;
        if ($withRequestAnalyzer) {
            $requestAnalyzerMock = $this->getMockBuilder(
                'Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface'
            )->getMock();

            $security = new Security();
            $security->setSystem($system);

            $webspace = new Webspace();
            $webspace->setSecurity($security);

            $requestAnalyzerMock->expects($this->once())->method('getCurrentWebspace')->will(
                $this->returnValue($webspace)
            );
        }

        return new UserRepositoryFactory($entityManagerMock, 'Sulu', $requestAnalyzerMock);
    }

    public function testGetRepositoryWithoutRequestAnalyzer()
    {
        $userRepositoryFactory = $this->getUserRepositoryFactoryMock('Sulu', false);

        $userRepositoryFactory->getRepository();
    }

    public function testGetRepositoryWithRequestAnalyzer()
    {
        $userRepositoryFactory = $this->getUserRepositoryFactoryMock('massiveart', true);

        $userRepositoryFactory->getRepository();
    }
}
