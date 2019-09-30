<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\Preview;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\PreviewBundle\Controller\PreviewController;
use Sulu\Bundle\PreviewBundle\Preview\PreviewInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PreviewControllerTest extends TestCase
{
    /**
     * @var PreviewInterface
     */
    private $preview;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var PreviewController
     */
    private $previewController;

    /**
     * @var int
     */
    private $encodingOptions = JsonResponse::DEFAULT_ENCODING_OPTIONS;

    protected function setUp(): void
    {
        $this->preview = $this->prophesize(PreviewInterface::class);
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);

        $this->previewController = new PreviewController($this->preview->reveal(), $this->tokenStorage->reveal());

        $token = $this->prophesize(TokenInterface::class);
        $user = $this->prophesize(UserInterface::class);
        $this->tokenStorage->getToken()->willReturn($token->reveal());
        $token->getUser()->willReturn($user->reveal());
        $user->getId()->willReturn(42);
    }

    public function testStart()
    {
        $request = $this->prophesize(Request::class);
        $request->get('id', null)->willReturn('123-123-123');
        $request->get('provider', null)->willReturn('test-provider');
        $request->get('locale', null)->willReturn('de');

        $this->preview->start('test-provider', '123-123-123', 'de', 42)
            ->shouldBeCalled()
            ->willReturn('test-token');

        $response = $this->previewController->startAction($request->reveal());
        $this->assertEquals(json_encode(['token' => 'test-token']), $response->getContent());
    }

    public function testRender()
    {
        $request = $this->prophesize(Request::class);
        $request->get('token', null)->willReturn('test-token');
        $request->get('webspace', null)->willReturn('sulu_io');
        $request->get('locale', null)->willReturn('de');
        $request->get('id', null)->willReturn('123-123-123');
        $request->get('provider', null)->willReturn('test-provider');
        $request->get('targetGroup', null)->willReturn(1);

        $this->preview->exists('test-token')->willReturn(true)->shouldBeCalled();
        $this->preview->render('test-token', 'sulu_io', 'de', 1)
            ->shouldBeCalled()
            ->willReturn('<html><body><h1>SULU is awesome</h1></body></html>');

        $response = $this->previewController->renderAction($request->reveal());
        $this->assertEquals('<html><body><h1>SULU is awesome</h1></body></html>', $response->getContent());
    }

    public function testRenderInvalidToken()
    {
        $request = $this->prophesize(Request::class);
        $request->get('token', null)->willReturn('test-token');
        $request->get('webspace', null)->willReturn('sulu_io');
        $request->get('locale', null)->willReturn('de');
        $request->get('id', null)->willReturn('123-123-123');
        $request->get('provider', null)->willReturn('test-provider');
        $request->get('targetGroup', null)->willReturn(1);

        $token = $this->prophesize(TokenInterface::class);
        $user = $this->prophesize(UserInterface::class);
        $this->tokenStorage->getToken()->willReturn($token->reveal());
        $token->getUser()->willReturn($user->reveal());
        $user->getId()->willReturn(42);

        $this->preview->exists('test-token')->willReturn(false)->shouldBeCalled();
        $this->preview->start('test-provider', '123-123-123', 'de', 42)
            ->willReturn('test-token');
        $this->preview->render('test-token', 'sulu_io', 'de', 1)
            ->shouldBeCalled()
            ->willReturn('<html><body><h1>SULU is awesome</h1></body></html>');

        $response = $this->previewController->renderAction($request->reveal());
        $this->assertEquals('<html><body><h1>SULU is awesome</h1></body></html>', $response->getContent());
    }

    public function testRenderWithATags()
    {
        $request = $this->prophesize(Request::class);
        $request->get('token', null)->willReturn('test-token');
        $request->get('webspace', null)->willReturn('sulu_io');
        $request->get('locale', null)->willReturn('de');
        $request->get('id', null)->willReturn('123-123-123');
        $request->get('provider', null)->willReturn('test-provider');
        $request->get('targetGroup', null)->willReturn(1);

        $this->preview->exists('test-token')->willReturn(true)->shouldBeCalled();
        $this->preview->render('test-token', 'sulu_io', 'de', 1)
            ->shouldBeCalled()
            ->willReturn('<html><body><h1>SULU is awesome</h1></body></html>');

        $response = $this->previewController->renderAction($request->reveal());
        $this->assertEquals('<html><body><h1>SULU is awesome</h1></body></html>', $response->getContent());
    }

    public function testUpdate()
    {
        $request = $this->prophesize(Request::class);
        $request->get('token', null)->willReturn('test-token');
        $request->get('data', null)->willReturn(['title' => 'Sulu is awesome']);
        $request->get('webspace', null)->willReturn('sulu_io');
        $request->get('id', null)->willReturn('123-123-123');
        $request->get('provider', null)->willReturn('test-provider');
        $request->get('locale', null)->willReturn('de');
        $request->get('targetGroup', null)->willReturn(1);

        $this->preview->exists('test-token')->willReturn(true)->shouldBeCalled();
        $this->preview->update('test-token', 'sulu_io', ['title' => 'Sulu is awesome'], 1)
            ->shouldBeCalled()
            ->willReturn('<html><body><h1>SULU is awesome</h1></body></html>');

        $response = $this->previewController->updateAction($request->reveal());

        $this->assertEquals(
            json_encode(['content' => '<html><body><h1>SULU is awesome</h1></body></html>'], $this->encodingOptions),
            $response->getContent()
        );
    }

    public function testUpdateWithATags()
    {
        $request = $this->prophesize(Request::class);
        $request->get('token', null)->willReturn('test-token');
        $request->get('data', null)->willReturn(['title' => 'Sulu is awesome']);
        $request->get('webspace', null)->willReturn('sulu_io');
        $request->get('id', null)->willReturn('123-123-123');
        $request->get('provider', null)->willReturn('test-provider');
        $request->get('locale', null)->willReturn('de');
        $request->get('targetGroup', null)->willReturn(1);

        $this->preview->exists('test-token')->willReturn(true)->shouldBeCalled();
        $this->preview->update('test-token', 'sulu_io', ['title' => 'Sulu is awesome'], 1)
            ->shouldBeCalled()
            ->willReturn('<html><body><a href="/test">SULU is awesome</a></body></html>');

        $response = $this->previewController->updateAction($request->reveal());
        $this->assertEquals(
            json_encode(
                ['content' => '<html><body><a href="/test">SULU is awesome</a></body></html>'],
                $this->encodingOptions
            ),
            $response->getContent()
        );
    }

    public function testUpdateContext()
    {
        $request = $this->prophesize(Request::class);
        $request->get('token', null)->willReturn('test-token');
        $request->get('context', null)->willReturn(['template' => 'default']);
        $request->get('webspace', null)->willReturn('sulu_io');
        $request->get('id', null)->willReturn('123-123-123');
        $request->get('provider', null)->willReturn('test-provider');
        $request->get('locale', null)->willReturn('de');
        $request->get('targetGroup', null)->willReturn(1);

        $this->preview->exists('test-token')->willReturn(true)->shouldBeCalled();
        $this->preview->updateContext('test-token', 'sulu_io', ['template' => 'default'], 1)
            ->shouldBeCalled()
            ->willReturn('<html><body><h1>SULU is awesome</h1></body></html>');

        $response = $this->previewController->updateContextAction($request->reveal());
        $this->assertEquals(
            json_encode(['content' => '<html><body><h1>SULU is awesome</h1></body></html>'], $this->encodingOptions),
            $response->getContent()
        );
    }

    public function testUpdateContextWithATags()
    {
        $request = $this->prophesize(Request::class);
        $request->get('token', null)->willReturn('test-token');
        $request->get('context', null)->willReturn(['template' => 'default']);
        $request->get('webspace', null)->willReturn('sulu_io');
        $request->get('id', null)->willReturn('123-123-123');
        $request->get('provider', null)->willReturn('test-provider');
        $request->get('locale', null)->willReturn('de');
        $request->get('targetGroup', null)->willReturn(1);

        $this->preview->exists('test-token')->willReturn(true)->shouldBeCalled();
        $this->preview->updateContext('test-token', 'sulu_io', ['template' => 'default'], 1)
            ->shouldBeCalled()
            ->willReturn('<html><body><a href="/test">SULU is awesome</a></body></html>');

        $response = $this->previewController->updateContextAction($request->reveal());
        $this->assertEquals(
            json_encode(
                ['content' => '<html><body><a href="/test">SULU is awesome</a></body></html>'],
                $this->encodingOptions
            ),
            $response->getContent()
        );
    }
}
