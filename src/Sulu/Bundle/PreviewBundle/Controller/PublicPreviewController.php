<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Controller;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistry;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRendererInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Serializer\ArraySerializerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class PublicPreviewController
{
    use RequestParametersTrait;

    /**
     * @var PreviewRendererInterface
     */
    private $previewRenderer;

    /**
     * @var PreviewObjectProviderRegistry
     */
    private $previewObjectProviderRegistry;

    /**
     * @var PreviewRendererInterface|null
     */
    private $profiler;

    public function __construct(
        PreviewRendererInterface $previewRenderer,
        PreviewObjectProviderRegistry $previewObjectProviderRegistry,
        Profiler $profiler = null
    ) {
        $this->previewRenderer = $previewRenderer;
        $this->previewObjectProviderRegistry = $previewObjectProviderRegistry;
        $this->profiler = $profiler;
    }

    public function renderAction(string $token): Response
    {
        // TODO get from entity
        $locale = 'en';
        $providerKey = 'pages';
        $id = 'aac8f317-d479-457c-a6e4-d95a3f19c0a6';
        $options = [
            'webspaceKey'=>'example',
            'locale' => 'en',
            'targetGroupId' => -1,
        ];

        $provider = $this->previewObjectProviderRegistry->getPreviewObjectProvider($providerKey);
        $object = $provider->getObject($id, $locale);

        $content = $this->previewRenderer->render($object, $id, false, $options);

        $this->disableProfiler();

        return new Response($content, 200, ['Content-Type' => 'text/html']);
    }

    private function disableProfiler()
    {
        if (!$this->profiler) {
            return;
        }

        $this->profiler->disable();
    }
}
