<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Permission;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTestCase;
use Symfony\Component\Security\Core\SecurityContextInterface;

class SecurityCheckerTest extends ProphecyTestCase
{
    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var SecurityContextInterface
     */
    private $securityContext;

    public function setUp()
    {
        parent::setUp();

        $this->securityContext = $this->prophesize('Symfony\Component\Security\Core\SecurityContextInterface');
        $this->securityChecker = new SecurityChecker($this->securityContext->reveal());
    }

    public function testIsGrantedContext()
    {
        $this->securityContext->isGranted(
            array('permission' => 'view', 'locale' => 'de'),
            Argument::which('getSecurityContext', 'sulu.media.collection')
        )->willReturn(true);

        $granted = $this->securityChecker->checkPermission('sulu.media.collection', 'view', 'de');

        $this->assertTrue($granted);
    }

    public function testIsGrantedObject()
    {
        $object = new \stdClass();

        $this->securityContext->isGranted(
            array('permission' => 'view', 'locale' => 'de'),
            $object
        )->willReturn(true);

        $granted = $this->securityChecker->checkPermission($object, 'view', 'de');

        $this->assertTrue($granted);
    }

    public function testIsGrantedFail()
    {
        $this->setExpectedException(
            'Symfony\Component\Security\Core\Exception\AccessDeniedException',
            'Permission "view" in localization "de" not granted'
        );

        $this->securityContext->isGranted(
            array('permission' => 'view', 'locale' => 'de'),
            Argument::which('getSecurityContext', 'sulu.media.collection')
        )->willReturn(false);

        $this->securityChecker->checkPermission('sulu.media.collection', 'view', 'de');
    }

    public function testIsGrantedFailWithoutLanguage()
    {
        $this->setExpectedException(
            'Symfony\Component\Security\Core\Exception\AccessDeniedException',
            'Permission "view" in localization "" not granted'
        );

        $this->securityContext->isGranted(
            array('permission' => 'view'),
            Argument::which('getSecurityContext', 'sulu.media.collection')
        )->willReturn(false);

        $this->securityChecker->checkPermission('sulu.media.collection', 'view');
    }
}
