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
     * returns language code from request
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    private function getTemplateKey(Request $request)
    {
        return $this->getRequestParameter($request, 'template', true);
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
        $languageCode = $this->getLanguageCode($request);
        $templateKey = $this->getTemplateKey($request);

        if ($contentUuid === 'index') {
            /** @var ContentMapperInterface $contentMapper */
            $contentMapper = $this->get('sulu.content.mapper');
            $startPage = $contentMapper->loadStartPage($webspaceKey, $languageCode);
            $contentUuid = $startPage->getUuid();
        }

        if (!$preview->started($uid, $contentUuid, $webspaceKey, $languageCode)) {
            $preview->start($uid, $contentUuid, $webspaceKey, $templateKey, $languageCode);
        }

        $content = $preview->render($uid, $contentUuid, $templateKey, $languageCode, $webspaceKey);

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
                            'template' => $templateKey,
                            'language' => $languageCode
                        )
                    ),
                'wsUrl' => 'ws://' . $request->getHttpHost(),
                'wsPort' => $this->container->getParameter('sulu_content.preview.websocket.port'),
                'interval' => $this->container->getParameter('sulu_content.preview.fallback.interval'),
                'contentUuid' => $contentUuid,
                'webspaceKey' => $webspaceKey,
                'templateKey' => $templateKey,
                'languageCode' => $languageCode
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
        $languageCode = $this->getLanguageCode($request);
        $templateKey = $this->getTemplateKey($request);

        if (!$preview->started($uid, $contentUuid, $webspaceKey, $languageCode)) {
            $preview->start($uid, $contentUuid, $webspaceKey, $templateKey, $languageCode);
        }

        $preview->updateTemplate($uid, $contentUuid, $templateKey, $webspaceKey, $languageCode);

        // get changes from request
        $changes = $request->get('changes', false);
        if (!!$changes) {
            foreach ($changes as $property => $content) {
                $preview->update($uid, $contentUuid, $webspaceKey, $templateKey, $languageCode, $property, $content);
            }
        }

        return new JsonResponse();
    }

    /**
     * returns changes since last request
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $contentUuid
     * @return Response
     */
    public function changesAction(Request $request, $contentUuid)
    {
        $uid = $this->getUserId();
        $webspaceKey = $this->getWebspaceKey($request);
        $languageCode = $this->getLanguageCode($request);

        $changes = $this->getPreview()->getChanges($uid, $contentUuid, $webspaceKey, $languageCode);

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
