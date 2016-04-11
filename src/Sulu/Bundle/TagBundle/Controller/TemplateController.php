<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TemplateController extends Controller
{
    /**
     * Returns Template for tag list.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function tagListAction()
    {
        return $this->render('SuluTagBundle:Template:tag.list.html.twig');
    }
}
