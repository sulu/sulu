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
use Sulu\Bundle\ContentBundle\Preview\PreviewInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Stopwatch\Stopwatch;

class PreviewController extends Controller
{

    const PREVIEW_ID = 'sulu_content.preview';

    public function startAction($contentUuid)
    {
        // TODO workspace
        // TODO language
        $uid = $this->getUserId();
        $this->getPreview()->start($uid, $contentUuid, '', '');

        return new Response();
    }

    public function renderAction($contentUuid)
    {
        $uid = $this->getUserId();
        $content = $this->getPreview()->render($uid, $contentUuid);

        return $this->render('SuluContentBundle:Preview:render.html.twig', array('content' => $content));
    }

    public function updateAction($contentUuid, Request $request)
    {
        $uid = $this->getUserId();
        $property = $request->get('property');
        $value = $request->get('value');

        $this->getPreview()->update($uid, $contentUuid, $property, $value);

        return new Response();
    }

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
