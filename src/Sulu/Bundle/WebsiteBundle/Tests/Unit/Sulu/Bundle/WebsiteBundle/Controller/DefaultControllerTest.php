<?php

/*
 * This file is part of Sulu.
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

    /**
     * @param $getValueMap
     * @param $uri
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getRequestMock($requestUrl, $portalUrl, $redirectUrl = null)
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')->getMock();
        $request->expects($this->any())->method('get')->will(
            $this->returnValueMap(
                [
                    ['url', null, false, $portalUrl],
                    ['redirect', null, false, $redirectUrl],
                ]
            )
        );
        $request->expects($this->any())->method('getUri')->will($this->returnValue($requestUrl));

        return $request;
    }

    public function provideRedirectAction()
    {
        return [
            ['http://sulu.lo/articles?foo=bar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo/en/articles?foo=bar'],
            ['http://sulu.lo/articles?foo=bar&bar=boo', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo/en/articles?foo=bar&bar=boo'],
            ['http://sulu.lo/articles/?foo=bar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo/en/articles?foo=bar'],
            ['http://sulu.lo/articles/?foo=bar&bar=boo', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo/en/articles?foo=bar&bar=boo'],
            ['http://sulu.lo/en/articles/?foo=bar', 'sulu.lo', null, 'http://sulu.lo/en/articles?foo=bar'],
            ['http://sulu.lo/en/articles/?foo=bar&bar=boo', 'sulu.lo', null, 'http://sulu.lo/en/articles?foo=bar&bar=boo'],
            ['sulu.lo:8001/', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8001/en'],
            ['sulu.lo:8001/#foobar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8001/en#foobar'],
            ['sulu.lo:8001/articles#foobar', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8001/en/articles#foobar'],
            ['sulu-redirect.lo/', 'sulu-redirect.lo', 'sulu.lo', 'http://sulu.lo'],
            ['sulu-redirect.lo/', 'sulu-redirect.lo', 'sulu.lo', 'http://sulu.lo'],
            ['http://sulu.lo:8002/', 'sulu.lo', 'sulu.lo/en', 'http://sulu.lo:8002/en'],
            ['http://sulu.lo/articles', 'sulu.lo/en', 'sulu.lo/de', 'http://sulu.lo/de/articles'],
        ];
    }

    /**
     * @dataProvider provideRedirectAction
     */
    public function testRedirectAction($requestUri, $portalUrl, $redirectUrl, $expectedTargetUrl)
    {
        $request = $this->getRequestMock($requestUri, $portalUrl, $redirectUrl);

        $response = $this->defaultController->redirectWebspaceAction($request);

        $this->assertEquals($expectedTargetUrl, $response->getTargetUrl());
    }
}
