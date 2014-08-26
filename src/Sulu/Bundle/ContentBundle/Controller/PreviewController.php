<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller;

use DOMDocument;
use Sulu\Bundle\ContentBundle\Preview\PreviewInterface;
use Sulu\Bundle\ContentBundle\Preview\PreviewNotFoundException;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Rest\RequestParametersTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * handles preview with ajax calls and renders basic preview
 */
class PreviewController extends Controller
{
    /**
     * id of preview service
     */
    const PREVIEW_ID = 'sulu_content.preview';

    use RequestParametersTrait;

    /**
     * returns language code from request
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    private function getLanguageCode(Request $request)
    {
        return $this->getRequestParameter($request, 'language', true);
    }

    /**
     * returns webspace key from request
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    private function getWebspaceKey(Request $request)
    {
        return $this->getRequestParameter($request, 'webspace', true);
    }

    /**
     * starts a preview
     * @param Request $request
     * @param string $contentUuid
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function startAction(Request $request, $contentUuid)
    {
        $uid = $this->getUserId();
        $preview = $this->getPreview();

        $webspaceKey = $this->getWebspaceKey($request);
        $locale = $this->getLanguageCode($request);
        $data = $this->getRequestParameter($request, 'data');

        $result = $preview->start($uid, $contentUuid, $webspaceKey, $locale, $data);

        return new JsonResponse($result->toArray());
    }

    /**
     * stops a preview
     * @param Request $request
     * @param string $contentUuid
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function stopAction(Request $request, $contentUuid)
    {
        $uid = $this->getUserId();
        $preview = $this->getPreview();

        $webspaceKey = $this->getWebspaceKey($request);
        $locale = $this->getLanguageCode($request);
        $data = $this->getRequestParameter($request, 'data');

        $preview->stop($uid, $contentUuid, $webspaceKey, $locale, $data);

        return new JsonResponse($result);
    }

    /**
     * render content for logged in user with data from FORM
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $contentUuid
     * @return Response
     */
    public function renderAction(Request $request, $contentUuid)
    {
        $uid = $this->getUserId();
        $preview = $this->getPreview();

        $webspaceKey = $this->getWebspaceKey($request);
        $locale = $this->getLanguageCode($request);

        if ($contentUuid === 'index') {
            /** @var ContentMapperInterface $contentMapper */
            $contentMapper = $this->get('sulu.content.mapper');
            $startPage = $contentMapper->loadStartPage($webspaceKey, $locale);
            $contentUuid = $startPage->getUuid();
        }

        try {
            $content = $preview->render($uid, $contentUuid, $webspaceKey, $locale);
        } catch (PreviewNotFoundException $ex) {
            return new JsonResponse($ex->toArray(), 404);
        }

        $script = $this->render(
            'SuluContentBundle:Preview:script.html.twig',
            array(
                'debug' => $this->get('kernel')->getEnvironment() === 'dev',
                'userId' => $uid,
                'ajaxUrl' => $this->generateUrl(
                        'sulu_content.preview.changes',
                        array(
                            'contentUuid' => $contentUuid,
                            'webspace' => $webspaceKey,
                            'language' => $locale
                        )
                    ),
                'wsUrl' => 'ws://' . $request->getHttpHost(),
                'wsPort' => $this->container->getParameter('sulu_content.preview.websocket.port'),
                'interval' => $this->container->getParameter('sulu_content.preview.fallback.interval'),
                'contentUuid' => $contentUuid,
                'webspaceKey' => $webspaceKey,
                'languageCode' => $locale
            )
        );

        $doc = new DOMDocument();
        $doc->encoding = 'utf-8';

        // FIXME hack found in http://stackoverflow.com/a/6090728
        libxml_use_internal_errors(true);
        $doc->loadHTML(utf8_decode($content));
        $errors = json_encode(libxml_get_errors());
        /** @var \Symfony\Bridge\Monolog\Logger $logger */
        $logger = $this->get('logger');
        $logger->debug('ERRORS in Preview Template: ' . $errors);
        libxml_clear_errors();

        $body = $doc->getElementsByTagName('body');
        $body = $body->item(0);

        $fragment = $doc->createDocumentFragment();
        $fragment->appendXML($script->getContent());
        $body->appendChild($fragment);
        $doc->formatOutput = true;

        $content = $doc->saveHTML();

        return new Response($content);
    }

    /**
     * updates a property in cache
     * @param Request $request
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $contentUuid
     * @return Response
     */
    public function updateAction(Request $request, $contentUuid)
    {
        $preview = $this->getPreview();
        $uid = $this->getUserId();

        $webspaceKey = $this->getWebspaceKey($request);
        $locale = $this->getLanguageCode($request);

        if (!$preview->started($uid, $contentUuid, $webspaceKey, $locale)) {
            $preview->start($uid, $contentUuid, $webspaceKey, $locale);
        }

        // get changes from request
        $changes = $request->get('changes', false);
        $result = $preview->updateProperties($uid, $contentUuid, $webspaceKey, $locale, $changes);

        return new JsonResponse($result->toArray());
    }

    /**
     * returns changes since last request
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $contentUuid
     * @return Response
     */
    public function changesAction(Request $request, $contentUuid)
    {
        $preview = $this->getPreview();
        $uid = $this->getUserId();

        $webspaceKey = $this->getWebspaceKey($request);
        $locale = $this->getLanguageCode($request);

        $changes = $preview->getChanges($uid, $contentUuid, $webspaceKey, $locale);

        return new JsonResponse($changes);
    }

    /**
     * @return PreviewInterface
     */
    private function getPreview()
    {
        return $this->get(self::PREVIEW_ID);
    }

    /**
     * @return int
     */
    private function getUserId()
    {
        return $this->getUser()->getId();
    }

}
