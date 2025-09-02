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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\PreviewBundle\Domain\Model\PreviewLinkInterface;
use Sulu\Bundle\PreviewBundle\Domain\Repository\PreviewLinkRepositoryInterface;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistryInterface;
use Sulu\Bundle\PreviewBundle\Preview\Provider\PreviewDefaultsProviderInterface;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRendererInterface;
use Sulu\Bundle\PreviewBundle\UserInterface\Controller\PublicPreviewController;
use Twig\Environment;

class PublicPreviewControllerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<PreviewRendererInterface>
     */
    private $previewRenderer;

    /**
     * @var ObjectProphecy<PreviewObjectProviderRegistryInterface>
     */
    private $previewObjectProviderRegistry;

    /**
     * @var ObjectProphecy<PreviewLinkRepositoryInterface>
     */
    private $previewLinkRepository;

    /**
     * @var ObjectProphecy<Environment>
     */
    private $twig;

    /**
     * @var PublicPreviewController
     */
    private $publicPreviewController;

    protected function setUp(): void
    {
        $this->previewRenderer = $this->prophesize(PreviewRendererInterface::class);
        $this->previewObjectProviderRegistry = $this->prophesize(PreviewObjectProviderRegistryInterface::class);
        $this->previewLinkRepository = $this->prophesize(PreviewLinkRepositoryInterface::class);
        $this->twig = $this->prophesize(Environment::class);

        $this->publicPreviewController = new PublicPreviewController(
            $this->previewRenderer->reveal(),
            $this->previewObjectProviderRegistry->reveal(),
            $this->previewLinkRepository->reveal(),
            $this->twig->reveal()
        );
    }

    public function testPreview(): void
    {
        $options = [
            'webspace' => 'example',
        ];

        $previewLink = $this->prophesize(PreviewLinkInterface::class);
        $previewLink->increaseVisitCount()->shouldBeCalled();

        $this->previewLinkRepository->findByToken('1234567890123')->willReturn($previewLink->reveal());
        $this->previewLinkRepository->commit()->shouldBeCalled();

        $this->twig->render('@SuluPreview/PreviewLink/preview.html.twig', ['token' => '1234567890123'])
            ->willReturn('<html><body><h1>Public Preview</h1></body></html>');

        $response = $this->publicPreviewController->previewAction('1234567890123');

        $this->assertEquals('<html><body><h1>Public Preview</h1></body></html>', $response->getContent());
    }

    public function testRender(): void
    {
        $options = [
            'webspace' => 'example',
        ];

        $previewLink = $this->prophesize(PreviewLinkInterface::class);
        $previewLink->getResourceKey()->willReturn('pages');
        $previewLink->getResourceId()->willReturn('123-123-123');
        $previewLink->getLocale()->willReturn('de');
        $previewLink->getOptions()->willReturn($options);

        $this->previewLinkRepository->findByToken('1234567890123')->willReturn($previewLink->reveal());

        $page = $this->prophesize(\stdClass::class);

        $provider = $this->prophesize(PreviewDefaultsProviderInterface::class);
        $provider->getDefaults(Argument::cetera())->willReturn([
            'object' => $page->reveal(),
            '_controller' => 'SuluWebsiteBundle:Default:index',
        ]);

        $this->previewObjectProviderRegistry->getPreviewObjectProvider('pages')->willReturn($provider->reveal());

        $this->previewRenderer->render(
            [
                'object' => $page->reveal(),
                '_controller' => 'SuluWebsiteBundle:Default:index',
            ],
            '123-123-123',
            false,
            \array_merge($options, ['locale' => 'de'])
        )
            ->willReturn('<html><body><h1>Hello World</h1></body></html>');

        $response = $this->publicPreviewController->renderAction('1234567890123');

        $this->assertEquals('<html><body><h1>Hello World</h1></body></html>', $response->getContent());
    }

    public function testRenderNotFound(): void
    {
        $this->previewLinkRepository->findByToken('1234567890123')->willReturn(null);

        $this->twig->render('@SuluPreview/PreviewLink/not-found.html.twig')
            ->willReturn('<html><body><h1>Not found</h1></body></html>');

        $response = $this->publicPreviewController->renderAction('1234567890123');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('', $response->getContent());
    }
}
