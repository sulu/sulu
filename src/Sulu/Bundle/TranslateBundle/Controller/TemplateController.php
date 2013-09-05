<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class TemplateController extends Controller
{
    public function catalogueformAction()
    {
        return $this->render('SuluTranslateBundle:Template:catalogue.form.html.twig', array());
    }

    public function translationformAction()
    {
        return $this->render('SuluTranslateBundle:Template:translation.form.html.twig', array());
    }

    public function packagelistAction()
    {
        return $this->render('SuluTranslateBundle:Template:package.list.html.twig', array());
    }
}
