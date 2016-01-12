<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Tests\Unit\Request;

use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\CustomUrlBundle\Request\CustomUrlRequestProcessor;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Document\RouteDocument;
use Sulu\Component\CustomUrl\Manager\CustomUrlManager;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class CustomUrlRequestProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function dataProvider()
    {
        return [
            ['sulu.io', '/test', 'sulu.io/test', false],
            ['sulu.io', '/test.html', 'sulu.io/test', false],
            ['sulu.io', '/test.html', 'sulu.io/test', true, true],
            ['sulu.io', '/test.html', 'sulu.io/test', true, false, false],
            ['sulu.io', '/test.html', 'sulu.io/test', true, false, true, false],
            ['sulu.io', '/test.html', 'sulu.io/test', true, false, true, true, WorkflowStage::PUBLISHED],
            ['sulu.io', '/test.html', 'sulu.io/test', true, false, true, true, WorkflowStage::TEST],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testProcess(
        $host,
        $requestedUri,
        $route,
        $exists = true,
        $history = true,
        $published = true,
        $hasTarget = true,
        $workflowStage = WorkflowStage::PUBLISHED,
        $webspaceKey = 'sulu_io'
    ) {
        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn($webspaceKey);

        $requestAttributes = new RequestAttributes(['webspace' => $webspace->reveal()]);

        $request = $this->prophesize(Request::class);
        $request->getHost()->willReturn($host);
        $request->getRequestUri()->willReturn($requestedUri);
        $request->getPathInfo()->willReturn($requestedUri);
        $request->getScheme()->willReturn('http');

        $customUrlManager = $this->prophesize(CustomUrlManager::class);

        if (!$exists) {
            $customUrlManager->findRouteByUrl($route, $webspaceKey)->willReturn(null);
        } else {
            $routeDocument = $this->prophesize(RouteDocument::class);
            $routeDocument->isHistory()->willReturn($history);
            $routeDocument->getPath()->willReturn('/cmf/sulu_io/custom-urls/routes/' . $route);

            if ($history) {
                $target = $this->prophesize(RouteDocument::class);
                $target->getPath()->willReturn('/cmf/sulu_io/custom-urls/routes/' . $route . '-1');
                $routeDocument->getTargetDocument()->willReturn($target->reveal());
            } else {
                $customUrl = $this->prophesize(CustomUrlDocument::class);
                $customUrl->isPublished()->willReturn($published);
                $customUrl->getTargetLocale()->willReturn('de');
                $customUrlManager->findByUrl($route, $webspaceKey, 'de')->willReturn($customUrl->reveal());

                if ($hasTarget) {
                    $target = $this->prophesize(PageDocument::class);
                    $target->getWorkflowStage()->willReturn($workflowStage);
                    $customUrl->getTargetDocument()->willReturn($target->reveal());
                    if ($workflowStage === WorkflowStage::PUBLISHED && $published) {
                        $request->setLocale('de')->shouldBeCalled();
                    }
                } else {
                    $customUrl->getTargetDocument()->willReturn(null);
                }
                $routeDocument->getTargetDocument()->willReturn($customUrl->reveal());
            }

            $customUrlManager->findRouteByUrl($route, $webspaceKey)->willReturn($routeDocument->reveal());
        }

        $processor = new CustomUrlRequestProcessor($customUrlManager->reveal());
        $processor->process($request->reveal(), $requestAttributes);
    }
}
