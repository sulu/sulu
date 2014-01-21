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

use DateTime;
use DOMDocument;
use Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface;
use Sulu\Bundle\ContentBundle\Preview\PreviewInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Stopwatch\Stopwatch;

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
            $preview->start($uid, $contentUuid, '', '');
        }

        $content = $preview->render($uid, $contentUuid);

        $script = $this->render(
            'SuluContentBundle:Preview:script.html.twig',
            array(
                'url' => $this->generateUrl('sulu_content.preview.changes', array('contentUuid' => $contentUuid)),
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
        $doc->formatOutput = TRUE;

        $content = $doc->saveHTML();

        return $this->render(
            'SuluContentBundle:Preview:render.html.twig',
            array(
                'content' => $content
            )
        );
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
            $preview->start($uid, $contentUuid, '', '');
        }

        $multiple = $request->get('multiple', false);

        if (!$multiple) {
            $property = $request->get('property');
            $value = $request->get('value');
            $preview->update($uid, $contentUuid, $property, $value);
        } else {
            foreach ($multiple as $property => $content) {
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
