<?php

namespace Sulu\Bundle\TranslateBundle\Controller;

use Sulu\Bundle\AdminBundle\Admin\ContentNavigation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class NavigationController extends Controller
{
    // has to be the same as defined in service.yml
    const SERVICE_NAME = 'sulu_translate.admin.content_navigation';

    /**
     * Lists all the contacts or filters the contacts by parameters GET['type']
     * Special function for lists
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function contentAction()
    {
        $type = $this->getRequest()->get('type');

        /** @var ContentNavigation $contentNavigation */
        if ($this->has(self::SERVICE_NAME)) {
            $contentNavigation = $this->get(self::SERVICE_NAME);
        }

        return new Response(json_encode($contentNavigation->toArray($type)));
    }
}
