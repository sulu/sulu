<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;

class DefaultControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DefaultController
     */
    private $defaultController;

    protected function setUp()
    {
        $this->defaultController = new DefaultController();
    }

    public function testRedirectActionWithTrailingSlash()
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')->getMock();
        $request->expects($this->any())->method('get')->will(
            $this->returnValueMap(
                array(
                    array('url', null, false, 'sulu-redirect.lo'),
                    array('redirect', null, false, 'sulu.lo')
                )
            )
        );
        $request->expects($this->any())->method('getUri')->will($this->returnValue('sulu-redirect.lo/'));

        $response = $this->defaultController->redirectAction($request);

        $this->assertEquals('sulu.lo', $response->getTargetUrl());
    }
}
