<?php

namespace Sulu\Bundle\TranslateBundle\Controller;

use Sulu\Bundle\AdminBundle\Admin\ContentNavigation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class NavigationController extends Controller
{
    // has to be the same as defined in service.yml
    const SERVICE_NAME = 'sulu_translate.admin.content_navigation';

    /**
     * returns navigation by parameters GET['type']
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function contentAction(Request $request)
    {
        $type = $request->get('type');

        /** @var ContentNavigation $contentNavigation */
        if ($this->has(self::SERVICE_NAME)) {
            $contentNavigation = $this->get(self::SERVICE_NAME);
        }

        return new JsonResponse($contentNavigation->toArray($type));
    }
}
