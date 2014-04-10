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
use Sulu\Component\Rest\RequestParameters;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * handles preview with ajax cals and renders basic preview
 */
class PreviewController extends Controller
{

    const PREVIEW_ID = 'sulu_content.preview';

    use RequestParameters;

    /**
     * returns language code from request
     * @return string
     */
    private function getLanguageCode()
    {
        return $this->getRequestParameter($this->getRequest(), 'language', true);
    }

    /**
     * returns language code from request
     * @return string
     */
    private function getTemplateKey()
    {
        return $this->getRequestParameter($this->getRequest(), 'template', true);
    }

    /**
     * returns webspace key from request
     * @return string
     */
    private function getWebspaceKey()
    {
        return $this->getRequestParameter($this->getRequest(), 'webspace', true);
    }

    /**
     * render content for logged in user with data from FORM
     * @param string $contentUuid
     * @return Response
     */
    public function renderAction($contentUuid)
    {
        $uid = $this->getUserId();
        $preview = $this->getPreview();

        $webspaceKey = $this->getWebspaceKey();
        $languageCode = $this->getLanguageCode();
        $templateKey = $this->getTemplateKey();

        if ($contentUuid === 'index') {
            /** @var ContentMapperInterface $contentMapper */
            $contentMapper = $this->get('sulu.content.mapper');
            $startPage = $contentMapper->loadStartPage($webspaceKey, $languageCode);
            $contentUuid = $startPage->getUuid();
        }

        if (!$preview->started($uid, $contentUuid, $templateKey, $languageCode)) {
            $preview->start($uid, $contentUuid, $webspaceKey, $templateKey, $languageCode);
        }

        $content = $preview->render($uid, $contentUuid, $templateKey, $languageCode);

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
                'wsUrl' => 'ws://' . $this->getRequest()->getHttpHost(),
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
        $doc->loadHTML(utf8_decode($content));

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
     * @param string $contentUuid
     * @param Request $request
     * @return Response
     */
    public function updateAction($contentUuid, Request $request)
    {
        $preview = $this->getPreview();
        $uid = $this->getUserId();

        $webspaceKey = $this->getWebspaceKey();
        $languageCode = $this->getLanguageCode();
        $templateKey = $this->getTemplateKey();

        if (!$preview->started($uid, $contentUuid, $templateKey, $languageCode)) {
            $preview->start($uid, $contentUuid, $webspaceKey, $templateKey, $languageCode);
        }

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
     * @param string $contentUuid
     * @return Response
     */
    public function changesAction($contentUuid)
    {
        $uid = $this->getUserId();
        $languageCode = $this->getLanguageCode();
        $templateKey = $this->getTemplateKey();

        $changes = $this->getPreview()->getChanges($uid, $contentUuid, $templateKey, $languageCode);

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
