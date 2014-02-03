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

        if (!$preview->started($uid, $contentUuid)) {
            // TODO workspace
            // TODO language
            $language = $this->getRequest()->get('language', 'en');
            $webspace = $this->getRequest()->get('webspace', 'sulu_io');
            $preview->start($uid, $contentUuid, $webspace, $language);
        }

        $content = $preview->render($uid, $contentUuid);

        // FIXME make url and port dynamic
        $script = $this->render(
            'SuluContentBundle:Preview:script.html.twig',
            array(
                'userId' => $uid,
                'ajaxUrl' => $this->generateUrl('sulu_content.preview.changes', array('contentUuid' => $contentUuid)),
                'wsUrl' => 'localhost',
                'wsPort' => '9876',
                'contenUuid' => $contentUuid,
                'interval' => $this->container->getParameter('sulu_content.preview.interval')
            )
        );

        $doc = new DOMDocument();
        $doc->loadHTML($content);

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

        if (!$preview->started($uid, $contentUuid)) {
            // TODO workspace
            // TODO language
            $language = $this->getRequest()->get('language', 'en');
            $webspace = $this->getRequest()->get('webspace', 'sulu_io');
            $preview->start($uid, $contentUuid, $webspace, $language);
        }

        // get changes from request
        $changes = $request->get('changes', false);
        if (!!$changes) {
            foreach ($changes as $property => $content) {
                $preview->update($uid, $contentUuid, $property, $content);
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
