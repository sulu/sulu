<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Controller;


use Symfony\Bundle\TwigBundle\Controller\ExceptionController as BaseExceptionController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\FlattenException;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

class ExceptionController extends BaseExceptionController
{
    public function showAction(
        Request $request,
        FlattenException $exception,
        DebugLoggerInterface $logger = null,
        $_format = 'html'
    ) {
        if ($exception->getStatusCode() == 404) {
            return new Response(
                $this->twig->render(
                    'ClientWebsiteBundle:views:error404.html.twig',
                    array(
                        'path' => $request->getPathInfo(),
                        'navigation' => array()
                    )
                ), 404
            );
        } else {
            return parent::showAction($request, $exception, $logger, $_format);
        }
    }
}
