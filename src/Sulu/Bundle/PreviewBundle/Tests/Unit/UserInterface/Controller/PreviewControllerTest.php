<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\UserInterface\Controller;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PreviewBundle\Preview\PreviewInterface;
use Sulu\Bundle\PreviewBundle\UserInterface\Controller\PreviewController;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PreviewControllerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<PreviewInterface>
     */
    private $preview;

    /**
     * @var ObjectProphecy<TokenStorageInterface>
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

    public function testStart(): void
    {
        $request = new Request(
            [
                'id' => '123-123-123',
                'provider' => 'test-provider',
                'locale' => 'de',
            ]
        );

        $this->preview->start('test-provider', '123-123-123', 42, [], ['locale' => 'de'])->shouldBeCalled()->willReturn('test-token');

        $response = $this->previewController->startAction($request);
        $this->assertEquals(\json_encode(['token' => 'test-token']), $response->getContent());
    }

    public function testRender(): void
    {
        $request = new Request(
            [
                'token' => 'test-token',
                'webspaceKey' => 'sulu_io',
                'id' => '123-123-123',
                'provider' => 'test-provider',
                'locale' => 'de',
                'targetGroupId' => 1,
                'segmentKey' => 'w',
            ]
        );

        $this->preview->exists('test-token')->willReturn(true)->shouldBeCalled();
        $this->preview->render(
            'test-token',
            ['targetGroupId' => 1, 'segmentKey' => 'w', 'webspaceKey' => 'sulu_io', 'locale' => 'de']
        )->shouldBeCalled()->willReturn('<html><body><h1>SULU is awesome</h1></body></html>');

        $response = $this->previewController->renderAction($request);
        $this->assertEquals('<html><body><h1>SULU is awesome</h1></body></html>', $response->getContent());
    }

    public function testRenderInvalidToken(): void
    {
        $request = new Request(
            [
                'token' => 'test-token',
                'webspaceKey' => 'sulu_io',
                'id' => '123-123-123',
                'provider' => 'test-provider',
                'locale' => 'de',
                'targetGroupId' => 1,
                'segmentKey' => 'w',
            ]
        );

        $token = $this->prophesize(TokenInterface::class);
        $user = $this->prophesize(UserInterface::class);
        $this->tokenStorage->getToken()->willReturn($token->reveal());
        $token->getUser()->willReturn($user->reveal());
        $user->getId()->willReturn(42);

        $this->preview->exists('test-token')->willReturn(false)->shouldBeCalled();
        $this->preview->start(
            'test-provider',
            '123-123-123',
            42,
            [],
            ['webspaceKey' => 'sulu_io', 'locale' => 'de', 'segmentKey' => 'w', 'targetGroupId' => 1]
        )->willReturn('test-token');
        $this->preview->render(
            'test-token',
            ['targetGroupId' => 1, 'segmentKey' => 'w', 'webspaceKey' => 'sulu_io', 'locale' => 'de']
        )->shouldBeCalled()->willReturn('<html><body><h1>SULU is awesome</h1></body></html>');

        $response = $this->previewController->renderAction($request);
        $this->assertEquals('<html><body><h1>SULU is awesome</h1></body></html>', $response->getContent());
    }

    public function testRenderWithATags(): void
    {
        $request = new Request(
            [
                'token' => 'test-token',
                'webspaceKey' => 'sulu_io',
                'id' => '123-123-123',
                'provider' => 'test-provider',
                'locale' => 'de',
                'targetGroupId' => 1,
                'segmentKey' => 's',
            ]
        );

        $this->preview->exists('test-token')->willReturn(true)->shouldBeCalled();
        $this->preview->render(
            'test-token',
            ['targetGroupId' => 1, 'segmentKey' => 's', 'webspaceKey' => 'sulu_io', 'locale' => 'de']
        )->shouldBeCalled()->willReturn('<html><body><h1>SULU is awesome</h1></body></html>');

        $response = $this->previewController->renderAction($request);
        $this->assertEquals('<html><body><h1>SULU is awesome</h1></body></html>', $response->getContent());
    }

    public function testUpdate(): void
    {
        $request = new Request(
            [
                'token' => 'test-token',
                'webspaceKey' => 'sulu_io',
                'id' => '123-123-123',
                'provider' => 'test-provider',
                'locale' => 'de',
                'targetGroupId' => 1,
                'segmentKey' => 's',
            ],
            [
                'data' => ['title' => 'Sulu is awesome'],
            ]
        );

        $this->preview->exists('test-token')->willReturn(true)->shouldBeCalled();
        $this->preview->update(
            'test-token',
            ['title' => 'Sulu is awesome'],
            ['targetGroupId' => 1, 'segmentKey' => 's', 'webspaceKey' => 'sulu_io', 'locale' => 'de']
        )->shouldBeCalled()->willReturn('<html><body><h1>SULU is awesome</h1></body></html>');

        $response = $this->previewController->updateAction($request);

        $this->assertEquals(
            \json_encode(['content' => '<html><body><h1>SULU is awesome</h1></body></html>'], $this->encodingOptions),
            $response->getContent()
        );
    }

    public function testUpdateWithATags(): void
    {
        $request = new Request(
            [
                'token' => 'test-token',
                'webspaceKey' => 'sulu_io',
                'id' => '123-123-123',
                'provider' => 'test-provider',
                'locale' => 'de',
                'targetGroupId' => 1,
                'segmentKey' => 's',
            ],
            [
                'data' => ['title' => 'Sulu is awesome'],
            ]
        );

        $this->preview->exists('test-token')->willReturn(true)->shouldBeCalled();
        $this->preview->update(
            'test-token',
            ['title' => 'Sulu is awesome'],
            ['targetGroupId' => 1, 'segmentKey' => 's', 'webspaceKey' => 'sulu_io', 'locale' => 'de']
        )->shouldBeCalled()->willReturn('<html><body><a href="/test">SULU is awesome</a></body></html>');

        $response = $this->previewController->updateAction($request);
        $this->assertEquals(
            \json_encode(
                ['content' => '<html><body><a href="/test">SULU is awesome</a></body></html>'],
                $this->encodingOptions
            ),
            $response->getContent()
        );
    }

    public function testUpdateContext(): void
    {
        $request = new Request(
            [
                'token' => 'test-token',
                'webspaceKey' => 'sulu_io',
                'id' => '123-123-123',
                'provider' => 'test-provider',
                'locale' => 'de',
                'targetGroupId' => 1,
                'segmentKey' => 's',
            ],
            [
                'context' => ['template' => 'default'],
                'data' => ['title' => 'test'],
            ]
        );

        $this->preview->exists('test-token')->willReturn(true)->shouldBeCalled();
        $this->preview->updateContext(
            'test-token',
            ['template' => 'default'],
            ['title' => 'test'],
            ['targetGroupId' => 1, 'segmentKey' => 's', 'webspaceKey' => 'sulu_io', 'locale' => 'de']
        )->shouldBeCalled()->willReturn('<html><body><h1>SULU is awesome</h1></body></html>');

        $response = $this->previewController->updateContextAction($request);
        $this->assertEquals(
            \json_encode(['content' => '<html><body><h1>SULU is awesome</h1></body></html>'], $this->encodingOptions),
            $response->getContent()
        );
    }

    public function testUpdateContextWithATags(): void
    {
        $request = new Request(
            [
                'token' => 'test-token',
                'webspaceKey' => 'sulu_io',
                'id' => '123-123-123',
                'provider' => 'test-provider',
                'locale' => 'de',
                'targetGroupId' => 1,
                'segmentKey' => 'w',
            ],
            [
                'context' => ['template' => 'default'],
                'data' => ['title' => 'test'],
            ]
        );

        $this->preview->exists('test-token')->willReturn(true)->shouldBeCalled();
        $this->preview->updateContext(
            'test-token',
            ['template' => 'default'],
            ['title' => 'test'],
            ['targetGroupId' => 1, 'segmentKey' => 'w', 'webspaceKey' => 'sulu_io', 'locale' => 'de']
        )->shouldBeCalled()->willReturn('<html><body><a href="/test">SULU is awesome</a></body></html>');

        $response = $this->previewController->updateContextAction($request);
        $this->assertEquals(
            \json_encode(
                ['content' => '<html><body><a href="/test">SULU is awesome</a></body></html>'],
                $this->encodingOptions
            ),
            $response->getContent()
        );
    }
}
