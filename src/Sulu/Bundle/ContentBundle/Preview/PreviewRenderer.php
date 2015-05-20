<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Preview;

use Liip\ThemeBundle\ActiveTheme;
use Sulu\Component\Content\Structure\Page;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

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

    public function __construct(
        ActiveTheme $activeTheme,
        ControllerResolverInterface $controllerResolver,
        WebspaceManagerInterface $webspaceManager,
        RequestStack $requestStack
    ) {
        $this->activeTheme = $activeTheme;
        $this->controllerResolver = $controllerResolver;
        $this->webspaceManager = $webspaceManager;
        $this->requestStack = $requestStack;
    }

    /**
     * renders content with the real website controller.
     *
     * @param Page $content
     * @param bool $partial
     *
     * @return string
     */
    public function render(Page $content, $partial = false)
    {
        // set active theme
        $webspace = $this->webspaceManager->findWebspaceByKey($content->getWebspaceKey());
        $this->activeTheme->setName($webspace->getTheme()->getKey());

        // get controller and invoke action
        $request = new Request();
        $request->setLocale($content->getLanguageCode());
        $request->attributes->set('_controller', $content->getController());
        $controller = $this->controllerResolver->getController($request);

        $this->requestStack->push($request);
        /** @var Response $response */
        $response = $controller[0]->{$controller[1]}($content, true, $partial);
        $this->requestStack->pop();

        return $response->getContent();
    }
}
