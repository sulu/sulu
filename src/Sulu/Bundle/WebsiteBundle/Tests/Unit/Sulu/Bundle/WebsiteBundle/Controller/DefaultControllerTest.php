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

use Symfony\Component\HttpFoundation\Request;

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

    /**
     * @param $getValueMap
     * @param $uri
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getRequestMock($uri, $url, $redirect = null)
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')->getMock();
        $request->expects($this->any())->method('get')->will(
            $this->returnValueMap(
                array(
                    array('url', null, false, $url),
                    array('redirect', null, false, $redirect)
                )
            )
        );
        $request->expects($this->any())->method('getUri')->will($this->returnValue($uri));

        return $request;
    }

    public function testRedirectActionWithTrailingSlash()
    {
        $request = $this->getRequestMock('sulu-redirect.lo/', 'sulu-redirect.lo', 'sulu.lo');

        $response = $this->defaultController->redirectWebspaceAction($request);

        $this->assertEquals('sulu.lo', $response->getTargetUrl());
    }
}
