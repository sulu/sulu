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
     * @var Environment
     */
    private $engine;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var ArraySerializerInterface
     */
    private $serializer;

    /**
     * @var string
     */
    private $suluVersion;

    /**
     * @var string|null
     */
    private $appVersion;

    /**
     * @var array
     */
    private $translations;

    /**
     * @var string
     */
    private $fallbackLocale;

    /**
     * @var PreviewRendererInterface|null
     */
    private $profiler;

    public function __construct(
        PreviewRendererInterface $previewRenderer,
        PreviewObjectProviderRegistry $previewObjectProviderRegistry,
        Environment $engine,
        UrlGeneratorInterface $urlGenerator,
        WebspaceManagerInterface $webspaceManager,
        ArraySerializerInterface $serializer,
        string $suluVersion,
        ?string $appVersion,
        array $translations,
        string $fallbackLocale,
        Profiler $profiler = null
    ) {
        $this->previewRenderer = $previewRenderer;
        $this->previewObjectProviderRegistry = $previewObjectProviderRegistry;
        $this->engine = $engine;
        $this->urlGenerator = $urlGenerator;
        $this->webspaceManager = $webspaceManager;
        $this->serializer = $serializer;
        $this->suluVersion = $suluVersion;
        $this->appVersion = $appVersion;
        $this->translations = $translations;
        $this->fallbackLocale = $fallbackLocale;
        $this->profiler = $profiler;
    }

    public function indexAction(string $locale): Response
    {
        $endpoints = [
            'config' => $this->urlGenerator->generate('sulu_admin.config'),
            'items' => $this->urlGenerator->generate('sulu_page.get_items'),
            'loginCheck' => $this->urlGenerator->generate('sulu_admin.login_check'),
            'logout' => $this->urlGenerator->generate('sulu_admin.logout'),
            'profileSettings' => $this->urlGenerator->generate('sulu_security.patch_profile_settings'),
            'forgotPasswordReset' => $this->urlGenerator->generate('sulu_security.reset_password.email'),
            'resetPassword' => $this->urlGenerator->generate('sulu_security.reset_password.reset'),
            'translations' => $this->urlGenerator->generate('sulu_admin.translation'),
            'generateUrl' => $this->urlGenerator->generate('sulu_page.post_resourcelocator', ['action' => 'generate']),
            'routing' => $this->urlGenerator->generate('fos_js_routing_js'),
        ];

        $webspaces = $this->webspaceManager->getWebspaceCollection()->getWebspaces();
        \uasort($webspaces, function($w1, $w2) {
            return \strcmp($w1->getName(), $w2->getName());
        });

        return new Response($this->engine->render(
            '@SuluAdmin/Admin/preview.html.twig',
            [
                'locale' => $locale,
                'translations' => $this->translations,
                'fallback_locale' => $this->fallbackLocale,
                'endpoints' => $endpoints,
                'sulu_version' => $this->suluVersion,
                'app_version' => $this->appVersion,
                'webspaces' => $this->serializer->serialize(
                    $webspaces, SerializationContext::create()
                        ->setAttribute('locale', $locale)
                ),
            ]
        ));
    }

    public function renderAction(Request $request, string $locale, string $providerKey, string $id): Response
    {
        $provider = $this->previewObjectProviderRegistry->getPreviewObjectProvider($providerKey);
        $object = $provider->getObject($id, $locale);

        $options = $request->query->all();
        $options['locale'] = $locale;

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
