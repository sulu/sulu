<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Preview;

use Liip\ThemeBundle\ActiveTheme;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

class PreviewRenderer
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var ActiveTheme
     */
    private $activeTheme;

    /**
     * @var ControllerResolverInterface
     */
    private $controllerResolver;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    public function __construct(
        ActiveTheme $activeTheme,
        ControllerResolverInterface $controllerResolver,
        WebspaceManagerInterface $webspaceManager,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        RequestAnalyzerInterface $requestAnalyzer
    ) {
        $this->activeTheme = $activeTheme;
        $this->controllerResolver = $controllerResolver;
        $this->webspaceManager = $webspaceManager;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * renders content with the real website controller.
     *
     * @param PageBridge $content
     * @param bool       $partial
     *
     * @return string
     */
    public function render(PageBridge $content, $partial = false)
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        // set active theme
        $webspace = $this->webspaceManager->findWebspaceByKey($content->getWebspaceKey());
        $this->activeTheme->setName($webspace->getTheme()->getKey());

        $query = [];
        $request = [];
        $cookies = [];
        if ($currentRequest !== null) {
            $query = $currentRequest->query->all();
            $request = $currentRequest->request->all();
            $cookies = $currentRequest->cookies->all();
        }

        // get controller and invoke action
        $request = new Request($query, $request, [], $cookies);
        $request->attributes->set('_controller', $content->getController());
        $request->query->set('webspace', $content->getWebspaceKey());
        $request->query->set('locale', $content->getLanguageCode());
        $controller = $this->controllerResolver->getController($request);

        // prepare locale for translator and request
        $request->setLocale($content->getLanguageCode());
        $localeBefore = $this->translator->getLocale();
        $this->translator->setLocale($content->getLanguageCode());

        $this->requestAnalyzer->analyze($request);

        $this->requestStack->push($request);
        /** @var Response $response */
        $response = $controller[0]->{$controller[1]}($content, true, $partial);

        // roll back
        $this->requestStack->pop();
        $this->translator->setLocale($localeBefore);

        return $response->getContent();
    }
}
