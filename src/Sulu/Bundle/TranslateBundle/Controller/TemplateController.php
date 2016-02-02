<?php

/*
 * This file is part of Sulu.
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
    public function packageFormAction()
    {
        return $this->render('SuluTranslateBundle:Template:package.form.html.twig', []);
    }

    public function translationFormAction()
    {
        return $this->render('SuluTranslateBundle:Template:translation.form.html.twig', []);
    }

    public function packageListAction()
    {
        return $this->render('SuluTranslateBundle:Template:package.list.html.twig', []);
    }
}
