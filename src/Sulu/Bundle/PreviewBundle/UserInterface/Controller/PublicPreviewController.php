<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\UserInterface\Controller;

use Sulu\Bundle\PreviewBundle\Domain\Repository\PreviewLinkRepositoryInterface;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistryInterface;
use Sulu\Bundle\PreviewBundle\Preview\PreviewContext;
use Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRendererInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Twig\Environment;

/**
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 */
class PublicPreviewController
{
    use RequestParametersTrait;

    public function __construct(
        private PreviewRendererInterface $previewRenderer,
        private PreviewObjectProviderRegistryInterface $previewObjectProviderRegistry,
        private PreviewLinkRepositoryInterface $previewLinkRepository,
        private Environment $twig,
        private ?Profiler $profiler = null,
    ) {
    }

    public function previewAction(string $token): Response
    {
        $previewLink = $this->previewLinkRepository->findByToken($token);
        if (!$previewLink) {
            return new Response($this->twig->render('@SuluPreview/PreviewLink/not-found.html.twig'), 404);
        }

        $previewLink->increaseVisitCount();
        $this->previewLinkRepository->commit();

        return new Response($this->twig->render('@SuluPreview/PreviewLink/preview.html.twig', ['token' => $token]));
    }

    public function renderAction(string $token): Response
    {
        $previewLink = $this->previewLinkRepository->findByToken($token);
        if (!$previewLink) {
            return new Response(null, 404);
        }

        $resourceKey = $previewLink->getResourceKey();
        $resourceId = $previewLink->getResourceId();
        $locale = $previewLink->getLocale();
        $options = $previewLink->getOptions();
        $options['locale'] = $locale;

        $provider = $this->previewObjectProviderRegistry->getPreviewObjectProvider($resourceKey);
        $previewContext = new PreviewContext($resourceId, $locale);
        $object = $provider->getDefaults($previewContext);

        $content = $this->previewRenderer->render($object, $resourceId, false, $options);

        $this->disableProfiler();

        return new Response($content);
    }

    private function disableProfiler(): void
    {
        if (!$this->profiler) {
            return;
        }

        $this->profiler->disable();
    }
}
