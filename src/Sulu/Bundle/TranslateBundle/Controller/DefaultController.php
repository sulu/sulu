<?php

namespace Sulu\Bundle\TranslateBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('SuluTranslateBundle:Default:index.html.twig', array('name' => $name));
    }
}
