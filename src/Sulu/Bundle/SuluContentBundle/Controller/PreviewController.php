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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PreviewController extends Controller
{

    const PREVIEW_ID = 'sulu_content.preview';

    /**
     * render content for logedin user with data from FORM
     * @param string $contentUuid
     * @return Response
     */
    public function renderAction($contentUuid)
    {
        $uid = $this->getUserId();
        $preview = $this->getPreview();

        $language = $this->getRequest()->get('language', 'en');
        $webspace = $this->getRequest()->get('webspace', 'sulu_io');

        if ($contentUuid === 'index') {
            /** @var ContentMapperInterface $contentMapper */
            $contentMapper = $this->get('sulu.content.mapper');
            $startPage = $contentMapper->loadStartPage($webspace, $language);
            $contentUuid =$startPage->getUuid();
        }

        if (!$preview->started($uid, $contentUuid)) {
            $preview->start($uid, $contentUuid, $webspace, $language);
        }

        $content = $preview->render($uid, $contentUuid);

        $script = $this->render(
            'SuluContentBundle:Preview:script.html.twig',
            array(
                'userId' => $uid,
                'ajaxUrl' => $this->generateUrl('sulu_content.preview.changes', array('contentUuid' => $contentUuid)),
                'wsUrl' => 'ws://' . $this->getRequest()->getHttpHost(),
                'wsPort' => $this->container->getParameter('sulu_content.preview.websocket.port'),
                'contenUuid' => $contentUuid,
                'interval' => $this->container->getParameter('sulu_content.preview.fallback.interval')
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

        $language = $this->getRequest()->get('language', 'en');
        $webspace = $this->getRequest()->get('webspace', 'sulu_io');

        if (!$preview->started($uid, $contentUuid)) {
            $preview->start($uid, $contentUuid, $webspace, $language);
        }
        $template = $this->getRequest()->get('template');

        // get changes from request
        $changes = $request->get('changes', false);
        if (!!$changes) {
            foreach ($changes as $property => $content) {
                $preview->update($uid, $contentUuid, $webspace, $language, $property, $content, $template);
            }
        }

        return new Response();
    }

    /**
     * returns changes since last request
     * @param string $contentUuid
     * @return Response
     */
    public function changesAction($contentUuid)
    {
        $uid = $this->getUserId();
        $changes = $this->getPreview()->getChanges($uid, $contentUuid);

        return new Response(json_encode($changes));
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
        return $this->getUser()
            ->getId();
    }

}
