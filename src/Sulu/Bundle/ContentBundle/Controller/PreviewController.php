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

use Sulu\Bundle\ContentBundle\Preview\PreviewInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PreviewController extends Controller
{

    const PREVIEW_ID = 'sulu_content.preview';

    public function startAction($contentUuid)
    {
        // TODO workspace
        // TODO language
        $uid = $this->getUserId();
        $this->getPreview()->start($uid, $contentUuid, '', '');
        $content = $this->getPreview()->render($uid, $contentUuid);

        return $this->render('SuluContentBundle:Preview:preview-start.html.twig', array('content' => $content));
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
